# Gestion des travaux

Ce projet est la partie back-end de l'application Gestion Travaux PWA

Le projet continent les différentes APIs.
La documentation des api es accessible une fois lancé le serveur de l'application http://127.0.0.1:8000/api

## Installation

Pour utiliser cette fonctionnalité, il vous faut avoir installé [Docker]

Sinon vous devez seteup à un wamp/mamp ect ...

Si vous avez docker vous pouvez la commande suivante :
```bash
yarn docker build
```
Cela permet d'installer les images docker

Il faudra créer un ficher .env.local à la racine du projet et ajouter la ligne suivante :
DATABASE_URL="mysql://root:password@database/gestion_travaux?sslmode=disable&charset=utf8mb4"

Pour communiquer avec la base de données



## Commandes
```bash
php bin/console make:docker:database
docker-compose up -d

symfony console make:migration
symfony console doctrine:migrations:migrate

# Cette commande permet de vous connecter dans le container et développer
docker exec -it gestion-travaux-api bash

# A l’intérieur du container vous pouvez lancer les différentes commandes :
# Installe les dépendances
composer install

# Permet de créer la base de données et load les fixtures
yarn truncate-database

# Il faut installer les clés jwt:
php bin/console lexik:jwt:generate-keypair

# Une fois que vous tout installé vous pouvez lancer le serveur il sera accessible au http://127.0.0.1:8000/api (la doc de API Platform)
symfony server:start

```

## Exécuter la migration
```bash
    php bin/console doctrine:migrations:execute --down DoctrineMigrations\<Version> --quiet
```

## Lancer toutes les migrations non exécutées
```bash
php bin/console doctrine:migrations:migrate --quiet
```