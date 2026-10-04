# Design — Mot de passe oublié (OTP email) pour les clients

Date : 2026-10-04  
Statut : validé / implémenté

## Contexte

La page de connexion (`templates/security/login.html.twig`) n’expose aucun flux « Mot de passe oublié ». L’auth repose sur Symfony Security (session + form login). L’envoi d’emails passe par Symfony Mailer (`MAILER_DSN`). Un OTP 6 chiffres (HMAC-SHA256, 15 min) existe déjà pour l’**activation admin**, mais rien pour le reset client.

## Décisions validées

| Sujet | Choix |
|-------|--------|
| Vérification | **OTP par email** (6 chiffres), pas de lien magique |
| Périmètre | **Clients uniquement** (pas admin) — les admins gardent invitation/activation |
| Après reset | Flash succès + redirection `/login` (pas de connexion auto) |
| Email inconnu / non éligible | Message **générique** (anti-énumération) |
| Architecture | **Entité dédiée** `PasswordResetChallenge` (pas de champs OTP sur `User`) |

## Parcours utilisateur

1. Login → lien « Mot de passe oublié ? » (sous le champ mot de passe)
2. Saisie email → **toujours** message générique + écran OTP (anti-énumération)
3. Saisie OTP (renvoi possible avec cooldown, uniquement utile si challenge réel)
4. OTP valide → formulaire nouveau mot de passe + confirmation
5. Succès → flash + `/login`

Éligibilité pour envoi réel : utilisateur trouvé, **non admin**, `isEnabled = true`.  
Si non éligible : aucun email ; un **jeton factice** en session ouvre quand même l’écran OTP ; toute saisie / renvoi échoue avec les mêmes messages qu’un OTP incorrect.

## Modèle de données

### Entité `PasswordResetChallenge`

| Champ | Type | Contraintes / notes |
|-------|------|---------------------|
| `id` | int | PK auto |
| `user` | ManyToOne → `User` | non null, on delete cascade |
| `publicToken` | string(64) | unique, opaque (UUID hex / random), pour enchaîner les écrans |
| `otpCodeHash` | string(64) | HMAC-SHA256(`otp`, `APP_SECRET`) |
| `expiresAt` | datetime_immutable | création + 15 minutes |
| `attempts` | int | défaut 0 ; max **5** puis invalidation |
| `consumedAt` | datetime_immutable \| null | set après reset réussi |
| `createdAt` | datetime_immutable | |
| `lastSentAt` | datetime_immutable | cooldown renvoi **60 s** |

### Repository

- `findActiveByPublicToken(string $token): ?PasswordResetChallenge` — non consommé, non expiré
- `invalidateOpenChallengesForUser(User $user): void` — marque / supprime les challenges ouverts avant d’en créer un nouveau

### Migration

- `CREATE TABLE password_reset_challenge (...)`
- Index unique sur `public_token`
- Index sur `user_id` + `consumed_at` / `expires_at` selon besoin requête

## Architecture applicative

### Routes (publiques, hors auth)

| Méthode | Path | Name | Rôle |
|---------|------|------|------|
| GET\|POST | `/forgot-password` | `app_forgot_password` | Saisie email |
| GET\|POST | `/forgot-password/otp/{token}` | `app_forgot_password_otp` | Saisie OTP (+ renvoi) |
| GET\|POST | `/forgot-password/reset/{token}` | `app_forgot_password_reset` | Nouveau mot de passe |

### Composants

- `PasswordResetController` — orchestration HTTP, CSRF, flashes, redirects
- `PasswordResetService` — éligibilité, création challenge, vérif OTP, reset MDP, invalidation
- `PasswordResetMailer` — envoi email OTP via Mailer (From existant `commercial@duoimport.mg`)
- Forms Symfony :
  - `ForgotPasswordRequestType` — email
  - `ForgotPasswordOtpType` — otpCode
  - `ForgotPasswordResetType` — `plainPassword` `RepeatedType`, min 8 (aligné `UserType`)
- Templates Twig : carte Bootstrap alignée sur `security/login.html.twig`
  - `templates/security/forgot_password_request.html.twig`
  - `templates/security/forgot_password_otp.html.twig`
  - `templates/security/forgot_password_reset.html.twig`
  - `templates/emails/password_reset_otp.html.twig`
- Lien ajouté dans `templates/security/login.html.twig`

### Flux données (résumé)

```
POST email
  → toujours flash générique
  → si client éligible :
       invalider challenges ouverts
       créer challenge (OTP 6 chiffres, hash HMAC, token public, expires +15m)
       envoyer email OTP
       session.password_reset_token = publicToken
  → sinon :
       session.password_reset_token = jeton factice (ne correspond à aucun challenge)
  → redirect /forgot-password/otp/{token} (token = valeur session)

POST OTP (token)
  → si challenge actif introuvable (jeton factice / expiré) : même message d’échec
  → sinon : hash_equals, expiresAt, attempts < 5
  → OK : session.password_reset_verified_token = publicToken → redirect reset
  → KO : attempts++ ; si >= 5 invalider challenge

POST nouveau MDP (token, OTP déjà validé en session)
  → refuse si token ≠ session.password_reset_verified_token ou challenge invalide
  → hash password, flush User
  → consumedAt = now, invalider autres challenges, clear session keys
  → flash succès → /login
```

**Note session :** `password_reset_token` enchaîne les écrans ; `password_reset_verified_token` autorise uniquement l’étape reset après OTP OK.

## Règles métier & messages

| Cas | Comportement |
|-----|----------------|
| Email inconnu / admin / disabled | Message générique, pas d’email, jeton factice → écran OTP |
| Envoi OK (ou factice) | « Si un compte existe, un code a été envoyé. » |
| Échec Mailer | Flash danger ; ne pas laisser un challenge « fantôme » utilisable sans email (annuler / ne pas flush le challenge, ou le supprimer) |
| OTP invalide | « Code invalide ou expiré. » |
| 5 échecs / expiré / consommé | Refus ; inviter à recommencer depuis `/forgot-password` |
| Renvoi < 60 s | Message avec délai restant |
| Renvoi OK | Nouveau OTP, reset `attempts` et `expiresAt`, update `lastSentAt` |
| Reset OK | « Mot de passe mis à jour. Vous pouvez vous connecter. » → `/login` |

## Sécurité

- OTP jamais stocké en clair ; comparaison `hash_equals`
- Token public opaque (pas d’id user exposé)
- CSRF sur tous les formulaires
- Anti-énumération email
- Cooldown renvoi 60 s ; max 5 tentatives OTP ; TTL 15 min
- Un nouveau challenge invalide les précédents ouverts du même user
- Routes accessibles anonymes (firewall `main`)
- Si utilisateur déjà authentifié qui ouvre `/forgot-password` : redirect vers `app_user_profile`

## UI

- Style existant : card Bootstrap, header `bg-primary`, footer liens
- Lien « Mot de passe oublié ? » : sous le champ mot de passe, aligné à droite ou sous « Se souvenir de moi »
- Pas de refonte visuelle globale

## Tests ciblés

1. Client éligible → challenge créé + mail envoyé (Mailer mock)
2. Admin / inconnu / disabled → pas de mail, réponse générique
3. OTP correct → accès écran reset → MDP changé → redirect login
4. OTP incorrect / expiré / 5 essais → refus
5. Cooldown renvoi respecté
6. Challenge consommé non réutilisable

## Hors scope

- Reset pour comptes admin
- Connexion automatique après reset
- 2FA login quotidien
- Bundle `symfonycasts/reset-password-bundle`
- SMS OTP

## Fichiers principaux touchés / créés

- `src/Entity/PasswordResetChallenge.php` (nouveau)
- `src/Repository/PasswordResetChallengeRepository.php` (nouveau)
- `src/Service/PasswordResetService.php` (nouveau)
- `src/Service/PasswordResetMailer.php` (nouveau)
- `src/Controller/PasswordResetController.php` (nouveau)
- `src/Form/ForgotPassword*.php` (nouveaux)
- `templates/security/login.html.twig` (lien)
- `templates/security/forgot_password_*.html.twig` (nouveaux)
- `templates/emails/password_reset_otp.html.twig` (nouveau)
- `migrations/VersionYYYYMMDDHHMMSS.php` (nouveau)
- évent. `config/packages/security.yaml` si access_control explicite nécessaire
