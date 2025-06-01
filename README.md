# 🔧 Backend – Gestion Travaux (API Platform + Symfony)

Ce projet représente la partie back-end de l'application Gestion Travaux PWA.
Il expose une API RESTful générée automatiquement avec API Platform à partir des entités Symfony.

Une fois le projet lancé, la documentation interactive (Swagger UI) est accessible à :
📍 http://127.0.0.1:8000/api

## 📦 Stack technique

- Symfony 6

- API Platform – Génération automatique d’API REST & documentation

- MySQL – Base de données relationnelle

- JWT Auth – Authentification sécurisée

- Docker – Conteneurisation de l’environnement

## 🚀 Installation

### ✅ Option 1 – Avec Docker (recommandé)

1. Cloner le repo

```
    git clone <repo-url>
    cd gestion-travaux-backend
```

2. Créer un fichier .env.local

    DATABASE_URL="mysql://root:password@database/gestion_travaux?sslmode=disable&charset=utf8mb4"

3. Construire et lancer les conteneurs

```
    yarn docker build
    docker-compose up -d
```

4. Générer les clés JWT

```
    php bin/console lexik:jwt:generate-keypair
```

5. Installer les dépendances (dans le conteneur)

```
    docker exec -it gestion-travaux-api bash
    composer install
```

6. Préparer la base de données

```
    symfony console doctrine:database:create
    symfony console doctrine:migrations:migrate
    yarn truncate-database # (fixtures + reset)
```

7. Démarrer le serveur

`symfony server:start`

### 🔧 Option 2 – Sans Docker

Installez manuellement :

    PHP ≥ 8.1

    Composer

    MySQL

    Symfony CLI

Configurez ensuite .env.local et suivez les étapes 4–7 ci-dessus.

### 🧰 Commandes utiles

Action Commande
Accès conteneur :

```
    docker exec -it gestion-travaux-api bash
```

Lancer le serveur Symfony:

```
    symfony server:start
```

Générer migration :

```
    symfony console make:migration
```

Appliquer migration :

```
    symfony console doctrine:migrations:migrate
```

Revenir à une migration :

```
    php bin/console doctrine:migrations:execute --down DoctrineMigrations\\<Version>
```

Réinitialiser la base + fixtures:

```
    yarn truncate-database
```

## 🔐 Authentification

    JWT via LexikJWTAuthenticationBundle

    Nécessite génération de clés RSA (cf. étape 4)

## 🔗 Lien avec le frontend

Ce backend alimente l’application Gestion Travaux PWA.
L’interface utilisateur est développée avec Framework7 + Javascript/TypeScript, et consomme cette API REST.
