# Design — Pages légales éditables (CGV + Politique de confidentialité)

Date : 2026-09-26  
Statut : en attente de validation utilisateur

## Contexte

Les CGV (`/cgv`) et la politique de confidentialité (`/politique-de-confidentialite`) sont aujourd’hui figées dans des templates Twig. Le contenu des CGV évolue et doit aussi alimenter le PDF d’offre. Il faut permettre leur édition depuis l’admin, avec un éditeur riche, et une sauvegarde en base.

## Décisions validées

- Éditeur : **TinyMCE** (CDN)
- UI admin : **une page** « Pages légales » avec **2 onglets**
- Stockage : **approche A** — table `legal_page` (une ligne par document, identifiée par `slug`)

## Modèle de données

### Entité `LegalPage`

| Champ | Type | Contraintes |
|-------|------|-------------|
| `id` | int | PK auto |
| `slug` | string(64) | unique, non null — valeurs : `cgv`, `privacy_policy` |
| `title` | string(255) | non null |
| `content` | text (LONGTEXT) | HTML produit par TinyMCE |
| `updatedAt` | datetime_immutable | mis à jour à chaque save |

### Repository

- `findOneBySlug(string $slug): ?LegalPage`
- `getOrCreate(string $slug, string $defaultTitle = ''): LegalPage` — crée la ligne si absente (pattern proche de `QuoteSettingsRepository::getSettings()`)

### Migration

1. `CREATE TABLE legal_page (...)`
2. Seed de 2 lignes avec le HTML actuel extrait de :
   - `templates/cgv/_content.html.twig` (titre : « Conditions Générales de Vente »)
   - `templates/page/privacy_policy.html.twig` (corps, hors titre / date dynamique)

## Admin

### Route

- `GET|POST /admin/legal-pages` — nom : `app_admin_legal_pages`
- Accès : `ROLE_ADMIN` (déjà couvert par `security.yaml` `^/admin`)

### Contrôleur

- Nouveau `AdminLegalPageController` (ou action dans `AdminController`)
- Charge les 2 pages via repository
- Formulaire Symfony unique ou deux formulaires (un par onglet) :
  - champs : `title` (TextType), `content` (TextareaType + TinyMCE)
- Sur submit d’un onglet : flush + flash + redirect (conserve l’onglet actif via query `?tab=cgv|privacy`)

### UI

- Template `templates/admin/legal_pages.html.twig` extends `admin/base.html.twig`
- Bootstrap tabs : CGV | Politique de confidentialité
- TinyMCE CDN (jsDelivr ou Tiny Cloud self-hosted free CDN) initialisé sur chaque textarea `content`
- Plugins utiles : lists, link, table, code, autoresize (pas d’upload image dans ce scope)
- Entrée sidebar : « Pages légales » (icône `scale` ou `file-text`), active si route `app_admin_legal_pages`

### Sécurité contenu

- Contenu HTML stocké tel quel ; affiché avec `|raw` uniquement sur les pages publiques / PDF dédiées
- TinyMCE limité aux balises sémantiques (h2–h4, p, ul, ol, li, strong, em, a, table) via `valid_elements` / `extended_valid_elements`

## Public

### CGV

- `CGVController` charge `LegalPage` slug `cgv`
- Template affiche `title` + `content|raw` dans le layout site
- Si contenu vide : fallback éventuel sur un message minimal (après seed, ne devrait pas arriver)

### Politique de confidentialité

- `PageController::privacyPolicy` charge slug `privacy_policy`
- Même pattern d’affichage
- La date « Dernière mise à jour » peut utiliser `legalPage.updatedAt` au lieu de `now`

### PDF offre

- `templates/pdf/quote_offer.html.twig` : remplacer `{% include 'cgv/_content.html.twig' %}` par le contenu DB passé depuis `PdfGenerator` (`cgv_html`)
- Styles `.cgv-page` conservés ; le HTML seed/éditeur doit rester compatible Dompdf (éviter classes Bootstrap dans le contenu stocké)

### Partial Twig `_content.html.twig`

- Après migration seed : le partial peut devenir un fallback de secours ou être retiré une fois le rendu 100 % DB
- Décision d’implémentation : garder le fichier comme fallback si `content` vide, sinon ne plus l’inclure dans le flux normal

## Hors scope

- Upload / médiathèque d’images dans TinyMCE
- Historique de versions / audit trail
- Multi-langue
- Prévisualisation publique depuis l’admin
- Édition des autres textes légaux éventuels

## Critères de succès

1. Un admin peut modifier CGV et confidentialité via TinyMCE et retrouver le contenu après rechargement
2. `/cgv` et la page confidentialité affichent le contenu en base
3. Un PDF d’offre nouvellement généré inclut la CGV issue de la base
4. Le menu admin expose l’entrée « Pages légales »
5. Les données actuelles ne sont pas perdues (seed migration)

## Fichiers principaux impactés (prévision)

- Nouveau : `Entity/LegalPage`, `Repository/LegalPageRepository`, migration, `AdminLegalPageController`, `Form/LegalPageType`, `templates/admin/legal_pages.html.twig`
- Modifié : `templates/admin/base.html.twig`, `CGVController`, `PageController`, `templates/cgv/index.html.twig`, `templates/page/privacy_policy.html.twig`, `PdfGenerator`, `templates/pdf/quote_offer.html.twig`
