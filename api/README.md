# Frig'o dons API

API construite avec [API Platform](https://api-platform.com/) (Symfony 8, PHP 8.4, PostgreSQL 18).

## Services Docker

- `caddy` : serveur web, expose l'API sur `api.{DOMAIN_NAME}` en production et `http://localhost:61100` en dev.
- `php-api` : l'application Symfony (`./php-api`).
- `worker` : exécute les tâches planifiées (`messenger:consume scheduler_default`), voir [Mails](#mails).
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

Pour limiter les essais d'adresses, `/login` accepte 10 tentatives par minute et par IP (429 au-delà).
Le client étant rendu côté serveur, il transmet l'IP du visiteur dans `X-Forwarded-For` : l'en-tête n'est pris en
compte que si la requête vient d'une IP listée dans `TRUSTED_PROXIES` (sinon la limite s'applique à l'IP du client SSR).
En production, il s'agit des [plages d'IP de Cloudflare](https://www.cloudflare.com/ips/), d'où partent les requêtes du Worker.

Pour créer un compte :

```bash
docker compose exec php-api bin/console app:add-user email@exemple.fr Prénom Nom "06 00 00 00 00" [--manager]
```

## Mails

Les mails sont envoyés par le `worker` grâce au [Scheduler de Symfony](https://symfony.com/doc/current/scheduler.html)
(voir `php-api/src/Schedule.php`), le dimanche matin (heure de Paris) :

- 6h, `app:missing-volunteer-mail` : toutes les deux semaines, les dates encore libres dans les deux semaines à venir,
  aux utilisateurs qui l'ont demandé ;
- 7h, `app:callback-mail` : le rappel de leurs dates de la semaine suivante, aux utilisateurs qui l'ont demandé.

Chaque envoi est enregistré dans la table `mail_batch`, ce qui évite les doublons. Les commandes peuvent être lancées
à la main (`docker compose exec php-api bin/console app:callback-mail`). En cas d'échec d'une tâche planifiée,
un mail est envoyé à `ALERT_EMAIL`.

En dev, les mails sont visibles dans Mailhog. Le `worker` doit être redémarré (`docker compose restart worker`)
pour prendre en compte les modifications du code.

## Formulaire de contact

`POST /contact` envoie le message à l'adresse `MAILER_REPLY_TO`. Il est protégé par :

- [ALTCHA](https://altcha.org/) : le widget récupère un challenge sur `GET /altcha-challenge` et envoie la solution
  dans le champ `altcha` (signé avec `ALTCHA_HMAC_KEY`, valable 10 minutes, utilisable une seule fois) ;
- un honeypot : si le champ caché `website` est rempli, le message est ignoré (sans erreur).

## Qualité

```bash
docker compose exec php-api vendor/bin/mago lint
docker compose exec php-api vendor/bin/mago fmt
docker compose exec php-api vendor/bin/mago analyze
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
exécute les migrations et arrête l'ancien conteneur. Les autres services (dont le `worker`) sont ensuite recréés.

Le serveur doit contenir un fichier `.env` avec les variables de `.env.dist` (et `COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml`).
