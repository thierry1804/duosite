# DuoSite

## À propos
DuoSite est un projet web basé sur Symfony 7.2. Ce projet est actuellement en phase initiale de développement.

## Prérequis
- PHP 8.2 ou supérieur
- Composer
- Extensions PHP requises : ctype, iconv

## Installation

1. Cloner le dépôt
```bash
git clone [url-du-dépôt]
cd duosite
```

2. Installer les dépendances
```bash
composer install
```

3. Configurer l'environnement
```bash
# Copier le fichier .env en .env.local et ajuster les paramètres
cp .env .env.local
```

4. Démarrer le serveur de développement
```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

## Structure du projet

- **src/** : Code source de l'application
  - **Controller/** : Contrôleurs de l'application
  - **Kernel.php** : Configuration du noyau Symfony
- **config/** : Fichiers de configuration
  - **routes.yaml** : Configuration des routes
  - **services.yaml** : Configuration des services
- **public/** : Fichiers accessibles publiquement
  - **index.php** : Point d'entrée de l'application
- **var/** : Fichiers temporaires et logs
- **vendor/** : Dépendances gérées par Composer
- **bin/** : Exécutables

## Dépendances principales

- symfony/console
- symfony/dotenv
- symfony/flex
- symfony/framework-bundle
- symfony/runtime
- symfony/yaml

## Développement

Le projet utilise les attributs PHP pour la configuration des routes. Pour ajouter une nouvelle page ou fonctionnalité, créez un contrôleur dans le répertoire `src/Controller/`.

## Environnements

- Développement : `.env.dev`
- Production : configurez les variables d'environnement appropriées

## Licence

Propriétaire - Tous droits réservés 