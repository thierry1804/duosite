# Duo Import MDG - Site Web

Ce projet est un site web moderne et intuitif pour une entreprise spécialisée dans l'importation de produits depuis la Chine. Le site présente les services de l'entreprise, permet aux clients de demander des devis et de suivre leurs projets d'importation.

## Fonctionnalités Implémentées

- **Présentation des Activités** : Section détaillant les services d'importation, les types de produits importés et les avantages des services.
- **Présentation de la Compagnie** : Page "Qui sommes-nous" décrivant l'histoire de l'entreprise, sa mission et ses valeurs.
- **Page de Contact** : Formulaire de contact permettant aux visiteurs d'envoyer des messages avec envoi d'email de confirmation.
- **Page de Demande de Devis** : Système complet de demande de devis avec formulaire multi-étapes et calcul dynamique des coûts.
- **Système d'Authentification** : Inscription, connexion et gestion de profil utilisateur.
- **Espace Client** : Tableau de bord où les utilisateurs peuvent suivre l'état de leurs demandes de devis et consulter leurs projets.
- **Espace Administrateur** : Interface de gestion pour les administrateurs permettant de gérer les utilisateurs et les paramètres de devis.
- **Conditions Générales de Vente** : Page dédiée aux CGV.
- **Système d'Emails** : Envoi automatique d'emails pour les confirmations de contact, d'inscription et de demande de devis.

## Technologies Utilisées

- **Backend** : Symfony 7.2
- **Frontend** : HTML5, CSS3, JavaScript, Bootstrap 5
- **Base de Données** : MySQL
- **Outils de Build** : Gulp, Node.js
- **Autres** : Font Awesome, Google Maps

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
│   ├── Controller/     # Contrôleurs Symfony
│   │   ├── AdminController.php       # Gestion de l'espace admin
│   │   ├── ContactController.php     # Gestion des contacts
│   │   ├── HomeController.php        # Page d'accueil
│   │   ├── QuoteController.php       # Système de devis
│   │   ├── SecurityController.php    # Authentification
│   │   └── UserController.php        # Espace utilisateur
│   ├── Entity/         # Entités Doctrine
│   │   ├── Contact.php               # Entité pour les messages de contact
│   │   ├── Quote.php                 # Entité pour les devis
│   │   ├── QuoteItem.php             # Entité pour les éléments de devis
│   │   ├── QuoteSettings.php         # Paramètres du système de devis
│   │   └── User.php                  # Entité utilisateur
│   ├── Form/           # Formulaires Symfony
│   └── Repository/     # Repositories Doctrine
├── templates/          # Templates Twig
│   ├── admin/          # Templates pour l'espace administrateur
│   ├── about/          # Templates pour la page "Qui sommes-nous"
│   ├── contact/        # Templates pour la page de contact
│   ├── home/           # Templates pour la page d'accueil
│   ├── quote/          # Templates pour le système de devis
│   ├── security/       # Templates pour l'authentification
│   ├── services/       # Templates pour la page des services
│   └── user/           # Templates pour l'espace utilisateur
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