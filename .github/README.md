# CI/CD pour Duo Import MDG

Ce dossier contient les configurations pour l'intégration continue et le déploiement continu (CI/CD) du projet Duo Import MDG.

## Workflow GitHub Actions

Le workflow principal est défini dans le fichier `.github/workflows/symfony.yml` et effectue les opérations suivantes :

### Intégration Continue (CI)

À chaque push sur les branches `main` ou `master` et à chaque pull request vers ces branches, le workflow exécute automatiquement :

1. **Validation de la syntaxe PHP** : Vérifie que tous les fichiers PHP ne contiennent pas d'erreurs de syntaxe.
2. **Validation des fichiers composer** : S'assure que les fichiers `composer.json` et `composer.lock` sont valides.
3. **Installation des dépendances** : Installe toutes les dépendances du projet via Composer.
4. **Vérification des prérequis Symfony** : S'assure que l'environnement est correctement configuré pour Symfony.
5. **Nettoyage du cache** : Vide le cache de Symfony.

### Déploiement Continu (CD) - À configurer

La section de déploiement est actuellement commentée dans le fichier de workflow. Pour l'activer, vous devrez :

1. Décommenter la section `deploy` dans le fichier `.github/workflows/symfony.yml`.
2. Configurer les secrets GitHub suivants dans les paramètres de votre dépôt :
   - `HOST` : L'adresse du serveur de production.
   - `USERNAME` : Le nom d'utilisateur pour se connecter au serveur.
   - `SSH_PRIVATE_KEY` : La clé SSH privée pour l'authentification.
3. Ajuster le chemin vers votre projet sur le serveur de production.

## Ajout de Tests

Pour activer l'exécution des tests dans le workflow :

1. Créez un dossier `tests/` à la racine du projet.
2. Installez PHPUnit : `composer require --dev symfony/test-pack`.
3. Écrivez vos tests dans le dossier `tests/`.
4. Décommentez la section des tests dans le fichier de workflow.

## Personnalisation du Workflow

Vous pouvez personnaliser le workflow en modifiant le fichier `.github/workflows/symfony.yml` selon vos besoins spécifiques :

- Ajout d'étapes supplémentaires (analyse de code, vérification de sécurité, etc.).
- Modification des branches surveillées.
- Configuration de notifications (email, Slack, etc.).
- Ajout de matrices de test pour différentes versions de PHP ou de Symfony.

## Ressources Utiles

- [Documentation GitHub Actions](https://docs.github.com/en/actions)
- [Documentation Symfony sur les tests](https://symfony.com/doc/current/testing.html)
- [Documentation PHPUnit](https://phpunit.de/documentation.html) 