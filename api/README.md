# Frig'o dons API

API construite avec [API Platform](https://api-platform.com/) (Symfony 8, PHP 8.4, PostgreSQL 18).

## Services Docker

- `caddy` : serveur web, expose l'API sur `api.{DOMAIN_NAME}` en production et `http://localhost:61100` en dev.
- `php-api` : l'application Symfony (`./php-api`).
- `database` : PostgreSQL (port `61130` en dev).
- `mailhog` : capture des emails en dev (`http://localhost:61120`).
- `pg-backup` : sauvegarde horaire de la base vers S3 (prod uniquement).

## Démarrer en local

```bash
# Créer le fichier d'environnement (adapter les valeurs si besoin)
cp .env.dist .env

# Démarrer les services
docker compose up -d --build

# Installer les dépendances PHP
docker compose exec php-api composer install

# Exécuter les migrations
docker compose exec php-api bin/console doctrine:migrations:migrate

# Charger les fixtures de développement
docker compose exec php-api bin/console hautelook:fixtures:load -n --purge-with-truncate
```

La documentation de l'API est disponible sur [http://localhost:61100/docs](http://localhost:61100/docs).

## Authentification

Les bénévoles se connectent avec leur adresse e-mail uniquement, sans mot de passe :

```bash
curl -X POST http://localhost:61100/login -H 'Content-Type: application/json' -d '{"email": "manager1@frigodons.org"}'
```

L'API renvoie un JWT (valable 30 jours) à envoyer dans l'en-tête `Authorization: Bearer <token>` des requêtes suivantes.
Avec les fixtures, les comptes suivants sont disponibles : `manager1@frigodons.org`, `manager2@frigodons.org`
et `volunteer1@frigodons.org` à `volunteer10@frigodons.org`.

Pour créer un compte :

```bash
docker compose exec php-api bin/console app:add-user email@exemple.fr Prénom Nom "06 00 00 00 00" [--manager]
```

## Qualité

```bash
docker compose exec php-api vendor/bin/mago lint
docker compose exec php-api vendor/bin/mago fmt
```

## Mettre à jour le schéma de base de données

```bash
docker compose exec php-api bin/console doctrine:migrations:diff
docker compose exec php-api bin/console doctrine:migrations:migrate
```

## Importer une sauvegarde de production

```bash
docker compose cp <backup-file> database:/db-dump
docker compose exec -i database pg_restore --verbose -j8 -Ufrigodons -d frigodons /db-dump
```

## Déploiement

Le déploiement est fait par la CI GitHub Actions (`.github/workflows/api.yml`) à chaque push sur `main` :
les images sont construites, chargées sur le serveur puis `deploy.sh` démarre un nouveau conteneur `php-api`,
exécute les migrations et arrête l'ancien conteneur.

Le serveur doit contenir un fichier `.env` avec les variables de `.env.dist` (et `COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml`).
