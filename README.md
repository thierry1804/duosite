# Duo Import MDG - Site Web

Ce projet est un site web moderne et intuitif pour une entreprise spécialisée dans l'importation de produits depuis la Chine. Le site présente les services de l'entreprise, permet aux clients de demander des devis et de suivre leurs projets d'importation.

## Fonctionnalités

- **Présentation des Activités** : Section détaillant les services d'importation, les types de produits importés et les avantages des services.
- **Présentation de la Compagnie** : Page "Qui sommes-nous" décrivant l'histoire de l'entreprise, sa mission et ses valeurs, ainsi que la présentation de l'équipe et des témoignages de clients.
- **Page de Contact** : Formulaire de contact permettant aux visiteurs d'envoyer des messages et informations de contact (adresse, email, téléphone).
- **Page de Demande de Devis** : Formulaire permettant aux clients de demander un devis pour les services d'importation.
- **Espace Client** : Tableau de bord où les utilisateurs peuvent suivre l'état de leur demande de devis et gérer leurs projets d'importation.
- **Système de Paiement** : Fonctionnalité permettant le règlement des honoraires des prestations en ligne.

## Technologies Utilisées

- **Backend** : Symfony 7.2
- **Frontend** : HTML5, CSS3, JavaScript, Bootstrap 5
- **Base de Données** : MySQL (à implémenter)
- **Autres** : Font Awesome, Google Maps

## Structure du Projet

```
duosite/
├── config/             # Configuration Symfony
├── public/             # Fichiers publics
│   ├── css/            # Feuilles de style CSS
│   ├── js/             # Scripts JavaScript
│   └── images/         # Images et ressources graphiques
├── src/                # Code source PHP
│   ├── Controller/     # Contrôleurs Symfony
│   ├── Entity/         # Entités Doctrine (à implémenter)
│   ├── Form/           # Formulaires Symfony (à implémenter)
│   └── Repository/     # Repositories Doctrine (à implémenter)
├── templates/          # Templates Twig
│   ├── about/          # Templates pour la page "Qui sommes-nous"
│   ├── contact/        # Templates pour la page de contact
│   ├── home/           # Templates pour la page d'accueil
│   ├── quote/          # Templates pour la page de demande de devis
│   └── services/       # Templates pour la page des services
└── var/                # Fichiers générés (cache, logs)
```

## Installation

1. Cloner le dépôt :
   ```
   git clone https://github.com/votre-utilisateur/duosite.git
   cd duosite
   ```

2. Installer les dépendances :
   ```
   composer install
   ```

3. Configurer la base de données dans le fichier `.env` :
   ```
   DATABASE_URL=mysql://user:password@127.0.0.1:3306/duosite
   ```

4. Créer la base de données et exécuter les migrations :
   ```
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. Lancer le serveur de développement :
   ```
   symfony server:start
   ```

## Prochaines Étapes

- Implémentation de la base de données pour stocker les demandes de devis et les informations des clients.
- Développement du système d'authentification pour l'espace client.
- Intégration d'un système de paiement en ligne.
- Développement du backoffice pour la gestion des demandes de devis et des commandes.

## Licence

Ce projet est sous licence propriétaire. Tous droits réservés. 