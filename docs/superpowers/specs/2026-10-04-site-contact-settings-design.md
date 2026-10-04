# Design — Coordonnées du site paramétrables (backoffice)

Date : 2026-10-04  
Statut : validé / implémenté

## Contexte

Les coordonnées publiques (adresse, téléphones, email, horaires, réseaux sociaux) et le numéro WhatsApp sont **codés en dur** dans plusieurs templates Twig (`base.html.twig` footer + bouton flottant, page contact, tarifs, devis / Mobile Money). Seul `app.primary_contact_phone` dans `config/services.yaml` centralise partiellement un numéro pour le devis. Il n’existe pas de modèle type « settings société » ; le pattern le plus proche est le singleton `QuoteSettings`.

Objectif : permettre à un admin de paramétrer ces informations depuis le backoffice, avec un numéro WhatsApp choisi parmi la liste des téléphones.

## Décisions validées

| Sujet | Choix |
|-------|--------|
| Périmètre front | Footer, page contact, WhatsApp flottant, tarifs, devis / Mobile Money |
| Hors scope | Emails transactionnels, PDF, notices légales / suspended (inchangés) |
| Stockage | **Tables normalisées** (pas de JSON singleton, pas de `.env`) |
| WhatsApp | Case `isWhatsapp` sur un téléphone de la liste (**au plus un**) |
| Principal / Mobile Money | Case `isPrimary` indépendante (**au plus un**) |
| Horaires | Structure **par jour** (Lun→Dim) |
| Réseaux sociaux | Liste **dynamique** (réseau + URL) |
| WhatsApp non configuré | Bouton flottant **masqué** |
| Principal absent | Fallback = **premier** téléphone de la liste (ordre `position`) |
| Téléphones | **Au moins 1** requis à la sauvegarde admin |

## Modèle de données

### `SiteContactSettings` (singleton, 1 ligne)

| Champ | Type | Notes |
|-------|------|--------|
| `id` | int | PK |
| `addressLines` | text | Multilignes ; une ligne d’adresse par ligne |
| `email` | string | Email public affiché (`mailto:`) |
| `whatsappDefaultMessage` | string \| null | Texte prérempli `wa.me...?text=` |
| `mapEmbedUrl` | string \| null | URL iframe Google Maps (page contact) ; si vide, pas de carte |

### `ContactPhone` (ManyToOne → `SiteContactSettings`)

| Champ | Type | Notes |
|-------|------|--------|
| `id` | int | PK |
| `settings` | ManyToOne | cascade persist/remove, orphanRemoval |
| `label` | string \| null | Ex. « Thierry » (tarifs) |
| `number` | string | Affichage (`+261 38 42 711 68`) |
| `isWhatsapp` | bool | Au plus un `true` parmi tous les téléphones |
| `isPrimary` | bool | Au plus un `true` ; Mobile Money / numéro principal |
| `position` | int | Ordre d’affichage |

Digits pour `tel:` / `wa.me` : dérivés à l’affichage (strip espaces, garder chiffres ; préfixe pays sans `+` pour WhatsApp).

### `OpeningHour` (ManyToOne → `SiteContactSettings`)

| Champ | Type | Notes |
|-------|------|--------|
| `id` | int | PK |
| `settings` | ManyToOne | |
| `dayOfWeek` | smallint | 1 = Lundi … 7 = Dimanche |
| `isClosed` | bool | |
| `openTime` | time \| null | Requis si non fermé |
| `closeTime` | time \| null | Requis si non fermé ; doit être > `openTime` |

Exactement **7** lignes après seed / sauvegarde (une par jour).

### `SocialLink` (ManyToOne → `SiteContactSettings`)

| Champ | Type | Notes |
|-------|------|--------|
| `id` | int | PK |
| `settings` | ManyToOne | |
| `network` | string | Valeurs : `facebook`, `tiktok`, `instagram`, `linkedin`, `youtube`, `x`, `other` |
| `url` | string | URL valide ; lien non affiché si vide |
| `position` | int | Ordre d’affichage |

### Repository

- `SiteContactSettingsRepository::getSettings(): SiteContactSettings` — charge (ou crée) le singleton avec relations, sur le modèle de `QuoteSettingsRepository::getSettings()`.

### Migration + seed

Créer les tables + une ligne settings avec les valeurs actuelles du site :

- Adresse : Antsakambahiny / Ambohijanahary Antehiroka / Antananarivo - Madagascar (toutes les lignes affichées sur footer et page contact)
- Téléphones : les 3 numéros actuels ; `+261 38 42 711 68` → `isWhatsapp=true` et `isPrimary=true`
- Email : `contact@duoimport.mg`
- Horaires : Lun–Ven 08:00–17:00 ; Sam/Dim fermés
- Social : Facebook `https://www.facebook.com/duoimportmdg` ; TikTok à renseigner si URL connue (sinon entrée absente ou URL vide non affichée)
- Message WhatsApp : `Bonjour Duo Import MDG, `
- `mapEmbedUrl` : URL actuelle de l’iframe page contact

## Architecture applicative

### Backoffice

- Route admin `ROLE_ADMIN`, ex. `/admin/site-contact` (`app_admin_site_contact`)
- Entrée menu latéral « Coordonnées du site » (près de Paramètres des devis / Pages légales)
- Formulaire Symfony unique : settings + collections (`ContactPhone`, `OpeningHour` fixe 7 jours, `SocialLink`)
- Validation :
  - email valide
  - ≥ 1 téléphone
  - au plus un `isWhatsapp`, au plus un `isPrimary` (contrainte form + enforce à la sauvegarde)
  - horaires : si ouvert, heures présentes et `openTime` < `closeTime`
  - URLs (réseaux, carte) valides si renseignées
- UX : flash succès + redirect, comme `quote-settings`

### Couche lecture front

- Service `SiteContactProvider` :
  - `getSettings()`, `getPhones()`, `getWhatsappPhone()`, `getPrimaryPhone()`, `getOpeningHours()`, `getSocialLinks()`
  - Helpers URL : `getWhatsappUrl()`, digits `tel:`
  - Fallback primary = premier téléphone par `position` si aucun `isPrimary`
- Injection Twig globale `site_contact` (extension Twig ou global configuré) pour tous les templates

### Surfaces à brancher

| Surface | Comportement |
|---------|----------------|
| Footer (`base.html.twig`) | Adresse, téléphones, email, résumé horaires (jours ouverts), socials |
| Page contact | Même source + horaires détaillés + carte si `mapEmbedUrl` |
| Bouton WhatsApp flottant | Lien dynamique ; **masqué** si aucun `isWhatsapp` |
| Tarifs | Liste numéros (+ labels si présents) |
| Devis / Mobile Money | Numéro primary (sinon premier) ; remplacer l’usage de `app.primary_contact_phone` sur ces écrans |

Paramètre Symfony `app.primary_contact_phone` : peut rester temporairement pour compat, mais les écrans du périmètre doivent lire `SiteContactProvider`. Décision d’implémentation : **retirer l’injection sur QuoteController** pour ces écrans au profit du provider.

## Affichage horaires

- **Page contact** : une ligne par jour (ex. « Lundi - Vendredi : 8h00 - 17h00 » peut être regroupé si plages identiques consécutives, ou listé jour par jour — regroupement recommandé pour coller au design actuel).
- **Footer** : résumé compact des jours ouverts (ex. « Lun - Ven: 8h00 - 17h00 ») via helper de formatage.

## Gestion des erreurs / fallback

- Singleton absent → `getSettings()` crée + seed défaut (valeurs actuelles).
- Social sans URL → non rendu.
- Jour fermé → libellé « Fermé » sur la page contact.
- Aucun WhatsApp → bouton flottant absent (pas de fallback silencieux vers un autre numéro).

## Tests

- Unitaires provider : résolution WhatsApp, primary, fallback premier numéro, URL `wa.me`.
- Contraintes form : rejet si deux `isWhatsapp` ou deux `isPrimary` ; rejet si 0 téléphone ; horaires incohérents.
- Revue manuelle : footer, contact, WhatsApp, tarifs, devis — plus de hardcode des 3 numéros / email / adresse sur ces surfaces.

## Hors scope (explicite)

- Modification des emails métier (`commercial@`, `noreply@`, copies PDF).
- Contenu des pages légales (email dans le seed privacy).
- Chat WhatsApp autre que le bouton flottant + lien devis existant.
- Multi-langue des libellés de jours (français uniquement, comme aujourd’hui).

## Fichiers impactés (indicatif)

- Nouveaux : entités, repositories, forms, `SiteContactProvider`, Twig extension, template admin, migration
- Modifiés : `templates/admin/base.html.twig` (menu), `templates/base.html.twig`, `templates/contact/index.html.twig`, `templates/tarifs/index.html.twig`, `templates/quote/index.html.twig`, `QuoteController` (retirer dépendance `app.primary_contact_phone` pour l’affichage)
