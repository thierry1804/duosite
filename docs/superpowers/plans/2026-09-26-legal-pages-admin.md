# Legal Pages Admin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre l’édition admin (TinyMCE) des CGV et de la politique de confidentialité, stockées en base, affichées sur le site et dans le PDF d’offre.

**Architecture:** Table `legal_page` (slug unique + HTML). Page admin à 2 onglets. Controllers publics + `PdfGenerator` lisent le contenu via `LegalPageRepository`. Seed migration depuis fichiers HTML dédiés.

**Tech Stack:** Symfony Form, Doctrine ORM, TinyMCE CDN (jsDelivr), Twig, Dompdf, Bootstrap 5 tabs (admin).

**Spec:** `docs/superpowers/specs/2026-09-26-legal-pages-admin-design.md`

## Global Constraints

- TinyMCE via CDN (pas de npm/webpack)
- Slugs exacts : `cgv`, `privacy_policy`
- Accès admin : `ROLE_ADMIN` uniquement
- Contenu HTML affiché avec `|raw` uniquement sur pages dédiées / PDF
- Pas d’upload d’images TinyMCE, pas d’historique de versions
- Commits sans mention Cursor ; auteur git inchangé

## File map

| Fichier | Rôle |
|---------|------|
| `src/Entity/LegalPage.php` | Entité |
| `src/Repository/LegalPageRepository.php` | `findOneBySlug` / `getOrCreate` |
| `src/Form/LegalPageType.php` | Formulaire title + content |
| `src/Controller/AdminLegalPageController.php` | Admin 2 onglets |
| `migrations/Version20260926210000.php` | CREATE + seed |
| `migrations/data/legal_page_cgv.html` | HTML seed CGV |
| `migrations/data/legal_page_privacy.html` | HTML seed confidentialité |
| `templates/admin/legal_pages.html.twig` | UI TinyMCE |
| `templates/admin/base.html.twig` | Lien sidebar |
| `src/Controller/CGVController.php` | Charge DB |
| `src/Controller/PageController.php` | Charge DB |
| `templates/cgv/index.html.twig` | Affiche DB |
| `templates/page/privacy_policy.html.twig` | Affiche DB |
| `src/Service/PdfGenerator.php` | Passe `cgv_html` |
| `templates/pdf/quote_offer.html.twig` | Affiche `cgv_html` |

---

### Task 1: Entité + repository

**Files:**
- Create: `src/Entity/LegalPage.php`
- Create: `src/Repository/LegalPageRepository.php`

**Produces:**
- `LegalPage` avec `getSlug/setSlug`, `getTitle/setTitle`, `getContent/setContent`, `getUpdatedAt`, `touchUpdatedAt()`
- Constantes : `LegalPage::SLUG_CGV = 'cgv'`, `LegalPage::SLUG_PRIVACY = 'privacy_policy'`
- `LegalPageRepository::findOneBySlug(string $slug): ?LegalPage`
- `LegalPageRepository::getOrCreate(string $slug, string $defaultTitle = ''): LegalPage`

- [ ] **Step 1: Créer l’entité**

```php
<?php

namespace App\Entity;

use App\Repository\LegalPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegalPageRepository::class)]
#[ORM\Table(name: 'legal_page')]
#[ORM\UniqueConstraint(name: 'uniq_legal_page_slug', columns: ['slug'])]
class LegalPage
{
    public const SLUG_CGV = 'cgv';
    public const SLUG_PRIVACY = 'privacy_policy';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $slug = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touchUpdatedAt(): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
```

- [ ] **Step 2: Créer le repository**

```php
<?php

namespace App\Repository;

use App\Entity\LegalPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegalPage>
 */
class LegalPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalPage::class);
    }

    public function findOneBySlug(string $slug): ?LegalPage
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function getOrCreate(string $slug, string $defaultTitle = ''): LegalPage
    {
        $page = $this->findOneBySlug($slug);
        if ($page) {
            return $page;
        }

        $page = new LegalPage();
        $page->setSlug($slug);
        $page->setTitle($defaultTitle !== '' ? $defaultTitle : $slug);
        $page->setContent('');
        $this->getEntityManager()->persist($page);
        $this->getEntityManager()->flush();

        return $page;
    }
}
```

- [ ] **Step 3: Vérifier le mapping**

Run: `php bin/console doctrine:schema:validate --skip-sync`
Expected: mapping OK (table pas encore créée → warning sync acceptable) ou entity known.

- [ ] **Step 4: Commit**

```bash
git add src/Entity/LegalPage.php src/Repository/LegalPageRepository.php
git commit -m "Ajouter l'entité LegalPage et son repository."
```

---

### Task 2: Migration + seed HTML

**Files:**
- Create: `migrations/data/legal_page_cgv.html` — contenu actuel de `templates/cgv/_content.html.twig` (sans wrappers Twig)
- Create: `migrations/data/legal_page_privacy.html` — sections 1–8 de `templates/page/privacy_policy.html.twig` (sans h1 ni date)
- Create: `migrations/Version20260926210000.php`

**Consumes:** `LegalPage::SLUG_*` (valeurs string `cgv` / `privacy_policy`)

- [ ] **Step 1: Copier le HTML CGV actuel dans le fichier seed**

Copier le contenu brut de `templates/cgv/_content.html.twig` vers `migrations/data/legal_page_cgv.html` (HTML pur, pas de tags Twig).

- [ ] **Step 2: Créer le seed privacy**

HTML des sections 1–8 (h2 + p + ul) extrait de `templates/page/privacy_policy.html.twig`, sans la date dynamique.

- [ ] **Step 3: Migration**

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée legal_page et seed CGV + politique de confidentialité';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE legal_page (
            id INT AUTO_INCREMENT NOT NULL,
            slug VARCHAR(64) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_legal_page_slug (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $cgv = $this->loadSeed('legal_page_cgv.html');
        $privacy = $this->loadSeed('legal_page_privacy.html');
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->addSql(
            'INSERT INTO legal_page (slug, title, content, updated_at) VALUES (?, ?, ?, ?)',
            ['cgv', 'Conditions Générales de Vente', $cgv, $now]
        );
        $this->addSql(
            'INSERT INTO legal_page (slug, title, content, updated_at) VALUES (?, ?, ?, ?)',
            ['privacy_policy', 'Politique de confidentialité', $privacy, $now]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE legal_page');
    }

    private function loadSeed(string $filename): string
    {
        $path = dirname(__DIR__) . '/migrations/data/' . $filename;
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Seed file missing: %s', $path));
        }
        return file_get_contents($path);
    }
}
```

Note: si `addSql` avec params bindés n’est pas supporté comme ci-dessus sur cette version Doctrine, utiliser `$this->connection->insert('legal_page', [...])` dans `up()` après le CREATE.

- [ ] **Step 4: Exécuter uniquement cette migration**

Run: `php bin/console doctrine:migrations:execute --up "DoctrineMigrations\Version20260926210000" --no-interaction`

Expected: `[OK] Successfully migrated`

(Ne pas lancer `migrate` global : d’autres migrations orphelines échouent.)

- [ ] **Step 5: Commit**

```bash
git add migrations/Version20260926210000.php migrations/data/
git commit -m "Créer la table legal_page et peupler CGV et confidentialité."
```

---

### Task 3: Formulaire + admin TinyMCE

**Files:**
- Create: `src/Form/LegalPageType.php`
- Create: `src/Controller/AdminLegalPageController.php`
- Create: `templates/admin/legal_pages.html.twig`
- Modify: `templates/admin/base.html.twig` (lien sidebar après Paramètres devis)

**Consumes:** `LegalPageRepository::getOrCreate`, constantes slug

- [ ] **Step 1: Form type**

```php
<?php

namespace App\Form;

use App\Entity\LegalPage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LegalPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [new NotBlank(['message' => 'Le titre est obligatoire'])],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [new NotBlank(['message' => 'Le contenu est obligatoire'])],
                'attr' => [
                    'class' => 'form-control js-tinymce',
                    'rows' => 20,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LegalPage::class,
        ]);
    }
}
```

- [ ] **Step 2: Controller**

Route `GET|POST /admin/legal-pages` name `app_admin_legal_pages`.  
Query `?tab=cgv|privacy` (default `cgv`).  
Deux forms : `form_cgv`, `form_privacy` (noms `cgv` / `privacy`).  
Sur submit du form actif : `touchUpdatedAt()`, flush, flash, redirect avec tab.

- [ ] **Step 3: Template admin**

- Extends `admin/base.html.twig`
- Nav tabs Bootstrap
- Chaque pane : `form_start` + title + textarea `.js-tinymce` + submit
- Scripts block : TinyMCE 7 CDN jsDelivr

```html
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.5.1/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
  selector: 'textarea.js-tinymce',
  height: 480,
  menubar: false,
  plugins: 'lists link table code autoresize',
  toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code',
  valid_elements: 'h1,h2,h3,h4,p,br,ul,ol,li,strong/b,em/i,u,a[href|target|rel],table,thead,tbody,tr,th,td,section[class],span[class]',
  branding: false,
  license_key: 'gpl'
});
</script>
```

Avant submit : `tinymce.triggerSave()`.

- [ ] **Step 4: Sidebar**

Ajouter après Paramètres devis :

```twig
<li>
    <a href="{{ path('app_admin_legal_pages') }}" class="{{ app.request.get('_route') == 'app_admin_legal_pages' ? 'active' : '' }}">
        <i data-lucide="scale"></i> <span>Pages légales</span>
    </a>
</li>
```

- [ ] **Step 5: Smoke check**

Run: `php bin/console debug:router app_admin_legal_pages`  
Expected: route `/admin/legal-pages`

- [ ] **Step 6: Commit**

```bash
git add src/Form/LegalPageType.php src/Controller/AdminLegalPageController.php templates/admin/legal_pages.html.twig templates/admin/base.html.twig
git commit -m "Ajouter la gestion admin des pages légales avec TinyMCE."
```

---

### Task 4: Pages publiques + PDF

**Files:**
- Modify: `src/Controller/CGVController.php`
- Modify: `src/Controller/PageController.php`
- Modify: `templates/cgv/index.html.twig`
- Modify: `templates/page/privacy_policy.html.twig`
- Modify: `src/Service/PdfGenerator.php`
- Modify: `templates/pdf/quote_offer.html.twig`

**Consumes:** `LegalPageRepository::getOrCreate` / `findOneBySlug`

- [ ] **Step 1: CGVController**

Injecter `LegalPageRepository`, charger `getOrCreate(LegalPage::SLUG_CGV, 'Conditions Générales de Vente')`, passer `legalPage` au template.

- [ ] **Step 2: Template CGV**

```twig
{% extends 'base.html.twig' %}
{% block title %}{{ legalPage.title }} - DUO IMPORT MDG{% endblock %}
{% block body %}
<div class="container mt-4 mb-5">
    <h1 class="text-center mb-4">{{ legalPage.title }}</h1>
    <div class="cgv-web-content">
        {% if legalPage.content %}
            {{ legalPage.content|raw }}
        {% else %}
            {% include 'cgv/_content.html.twig' %}
        {% endif %}
    </div>
</div>
{% endblock %}
```

- [ ] **Step 3: Privacy controller + template**

Idem avec `SLUG_PRIVACY`. Afficher `Dernière mise à jour : {{ legalPage.updatedAt|date('d/m/Y') }}` puis `content|raw`. Fallback : ancien HTML hardcodé uniquement si content vide (optionnel ; seed doit remplir).

- [ ] **Step 4: PdfGenerator**

Injecter `LegalPageRepository` (ou charger dans `generateQuoteOfferPdf`). Passer :

```php
'cgv_html' => $legalPageRepository->findOneBySlug(LegalPage::SLUG_CGV)?->getContent() ?? '',
'cgv_title' => $legalPageRepository->findOneBySlug(LegalPage::SLUG_CGV)?->getTitle() ?? 'CONDITIONS GÉNÉRALES DE VENTE',
```

(Préférer un seul `findOneBySlug` stocké dans une variable.)

- [ ] **Step 5: PDF twig**

Remplacer include par :

```twig
<div class="cgv-page">
    <h1>{{ cgv_title|default('CONDITIONS GÉNÉRALES DE VENTE') }}</h1>
    {% if cgv_html %}
        {{ cgv_html|raw }}
    {% else %}
        {% include 'cgv/_content.html.twig' %}
    {% endif %}
</div>
```

- [ ] **Step 6: Commit**

```bash
git add src/Controller/CGVController.php src/Controller/PageController.php templates/cgv/index.html.twig templates/page/privacy_policy.html.twig src/Service/PdfGenerator.php templates/pdf/quote_offer.html.twig
git commit -m "Afficher CGV et confidentialité depuis la base sur le site et le PDF."
```

---

### Task 5: Vérification finale

- [ ] **Step 1:** `php bin/console debug:router | findstr legal`
- [ ] **Step 2:** Confirmer en SQL / console que 2 lignes existent dans `legal_page`
- [ ] **Step 3:** Checklist manuelle
  - Admin → Pages légales → éditer CGV → enregistrer → `/cgv` à jour
  - Idem privacy
  - Régénérer un PDF d’offre → dernière page = CGV DB

---

## Spec coverage check

| Spec item | Task |
|-----------|------|
| Entité LegalPage | 1 |
| Migration + seed | 2 |
| Admin TinyMCE 2 onglets | 3 |
| Sidebar | 3 |
| Public CGV / privacy | 4 |
| PDF CGV DB | 4 |
| Fallback content vide | 4 |
| Hors scope respecté | — |

## Placeholder scan

Aucun TBD / TODO / « similar to » restant.
