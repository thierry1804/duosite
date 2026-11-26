# Duo Import MDG - Site Web

Ce projet est un site web moderne et intuitif pour une entreprise spécialisée dans l'importation de produits depuis la Chine. Le site présente les services de l'entreprise, permet aux clients de demander des devis et de suivre leurs projets d'importation.

## Fonctionnalités Implémentées

- **Présentation des Activités** : Section détaillant les services d'importation, les types de produits importés et les avantages des services.
- **Présentation de la Compagnie** : Page "Qui sommes-nous" décrivant l'histoire de l'entreprise, sa mission et ses valeurs.
- **Page de Contact** : Formulaire de contact permettant aux visiteurs d'envoyer des messages avec envoi d'email de confirmation.
- **Page de Demande de Devis** : Système complet de demande de devis avec formulaire multi-étapes, calcul dynamique des coûts (taux de change RMB/MGA en direct) et activation/désactivation des demandes depuis l'admin.
- **Offres de Devis** : L'administrateur propose des lignes de produits (avec photos) en réponse à un devis ; le client choisit les lignes et quantités qu'il accepte, et reçoit un PDF (DomPDF) récapitulatif de l'offre acceptée.
- **Suivi des Commandes d'Import** : Historique de statut des devis et des commandes d'import, notifications SMS (API Orange) sur les étapes clés.
- **Système d'Authentification** : Inscription, connexion, gestion de profil utilisateur, réinitialisation de mot de passe par code OTP envoyé par email.
- **Espace Client** : Tableau de bord où les utilisateurs peuvent suivre l'état de leurs demandes de devis et consulter leurs projets.
- **Espace Administrateur** : Gestion des utilisateurs/admins, des paramètres de devis, des pages légales (CGV, mentions...), et des coordonnées de contact du site (téléphones, réseaux sociaux, horaires d'ouverture).
- **Conditions Générales de Vente** : Page dédiée aux CGV, éditable depuis l'admin.
- **Système d'Emails** : Envoi automatique d'emails pour les confirmations de contact, d'inscription, de demande de devis et d'offres.
- **Statistiques** : Intégration Google Analytics Data API (GA4) pour le reporting admin.

## Technologies Utilisées

- **Backend** : Symfony 7.2 (PHP 8.2+), Doctrine ORM
- **Frontend** : HTML5, CSS3, JavaScript, Bootstrap 5
- **Base de Données** : MySQL / MariaDB
- **Outils de Build** : Gulp, Node.js
- **Autres** : DomPDF (génération de PDF), API Orange SMS, Google Analytics Data API, Font Awesome, Google Maps

## Structure du Projet

```
duosite/
├── config/             # Configuration Symfony
├── migrations/         # Migrations de base de données
├── public/             # Fichiers publics
│   ├── css/            # Feuilles de style CSS
│   ├── js/             # Scripts JavaScript
│   └── images/         # Images et ressources graphiques
├── src/                # Code source PHP
│   ├── Controller/     # Contrôleurs Symfony (devis, offres, suivi, admin, auth, pages légales...)
│   ├── Entity/         # Entités Doctrine (Quote, QuoteOffer, ImportOrder, User, SiteContactSettings...)
│   ├── Service/        # Services métier (calcul des frais, taux de change, PDF, SMS, emails, GA4...)
│   ├── Form/           # Formulaires Symfony
│   ├── Repository/     # Repositories Doctrine
│   ├── Security/       # Authentification
│   ├── Shipping/       # Options d'expédition
│   └── Event/          # Événements applicatifs (ex: devis créé)
├── templates/          # Templates Twig (admin, quote, quote_offer, tracking, pdf, emails...)
├── tests/              # Tests PHPUnit (services, validateurs)
└── var/                # Fichiers générés (cache, logs)
```

## Installation

1. Cloner le dépôt :
   ```
   git clone https://github.com/votre-utilisateur/duosite.git
   cd duosite
   ```

2. Installer les dépendances PHP :
   ```
   composer install
   ```

3. Installer les dépendances Node.js :
   ```
   npm install
   ```

4. Configurer la base de données dans le fichier `.env.local` :
   ```
   DATABASE_URL=mysql://user:password@127.0.0.1:3306/duosite
   ```

5. Créer la base de données et exécuter les migrations :
   ```
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. Compiler les assets :
   ```
   npm run build
   ```

7. Lancer le serveur de développement :
   ```
   symfony server:start
   ```

## Fonctionnalités à Venir

- **Système de Paiement** : Intégration d'une passerelle de paiement pour le règlement des honoraires en ligne.
- **Suivi de Commande** : Système avancé de suivi des commandes et des expéditions.
- **Notifications en Temps Réel** : Alertes et notifications pour les mises à jour de statut des projets.
- **Espace de Discussion** : Messagerie intégrée entre clients et gestionnaires de projet.

## Licence

Ce projet est sous licence propriétaire. Tous droits réservés. 