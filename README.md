# Plateforme d'Apprentissage en Ligne

## Table des matières
1. [Prérequis](#prérequis)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Démarrage](#démarrage)
5. [Tests](#tests)
6. [Documentation](#documentation)

## Prérequis
- PHP 8.1 ou supérieur
- Composer
- Symfony CLI
- MySQL/MariaDB
- Node.js et npm
- Stripe (pour les paiements)
- Compte Gmail pour l'envoi d'emails

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/ton-utilisateur/knowledge-learning.git
cd knowledge-learning
```


### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Configuration

### 1. Variables d'environnement
Copier le fichier `.env` en `.env.local` et configurer :

```bash
DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name"
STRIPE_SECRET_KEY="sk_test_..."
STRIPE_PUBLIC_KEY="pk_test_..."
STRIPE_WEBHOOK_SECRET="whsec_..."

# Configuration Gmail
MAILER_DSN=gmail://YOUR_GMAIL:YOUR_APP_PASSWORD@default
MAILER_FROM_ADDRESS=your.email@gmail.com
MAILER_FROM_NAME="Your Application Name"
```

### 2. Configuration Gmail
1. Activer la validation en deux étapes sur votre compte Gmail
2. Générer un mot de passe d'application :
   - Aller dans les paramètres du compte Google
   - Sécurité > Connexion à Google > Mots de passe des applications
   - Créer un nouveau mot de passe d'application
3. Utiliser ce mot de passe dans MAILER_DSN

### 3. Stripe
- Créer un compte Stripe
- Configurer les webhooks
- Ajouter les clés API dans `.env.local`

## Démarrage

### 1. Lancer le serveur de développement

```bash
symfony serve -d
```

### 2. Compiler les assets

```bash
npm run dev
```

### 3. Créer un administrateur

```bash
php bin/console app:create-admin
```

L'application est accessible à : `http://http://127.0.0.1:8000/`

## Tests

### Lancer les tests unitaires

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/phpunit
```

## Documentation

### Générer la documentation
composer generate-doc

### Accéder à la documentation
1. Lancer un serveur local dans le dossier docs/api :
cd docs/api
php -S localhost:8000

2. Ouvrir dans le navigateur : `http://localhost:8000`

## Fonctionnalités principales
- Gestion des cursus et leçons
- Système de paiement via Stripe
- Validation des leçons
- Suivi de progression
- Interface administrateur
- Système de certification
- Envoi d'emails d'activation et de réinitialisation de mot de passe

## Support
Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe technique

## Licence
Ce projet est en accès gratuit
