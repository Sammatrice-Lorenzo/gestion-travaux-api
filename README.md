# Gestion des travaux
## Commande Docker
```bash
php bin/console make:docker:database
docker-compose up -d

symfony console make:migration
symfony console doctrine:migrations:migrate
```

mdp : 1234