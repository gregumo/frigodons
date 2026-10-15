# Frig'o dons

Le Frig'o dons est un réfrigérateur en libre service sur la commune de La Montagne (44).
Ce site permet aux bénévoles de La Cordée de s'organiser pour l'entretenir chaque jour.

## Organisation du dépôt

- [`api/`](api/README.md) : l'API, construite avec API Platform (Symfony 8, PHP 8.4, PostgreSQL 18), et son
  architecture Docker (Caddy, PHP-FPM, worker pour les mails planifiés, sauvegardes vers S3).
  Elle est servie sur `api.frigodons.org`.
- [`client/`](client/README.md) : le site, une application React Router 7 rendue côté serveur sur Cloudflare
  Workers, avec React Aria Components et Tailwind CSS. Il est servi sur `frigodons.org`.

Chaque partie est déployée par sa CI GitHub Actions (`.github/workflows/api.yml` et `client.yml`)
à chaque push sur `main`.

## Démarrer en local

```bash
cd api
cp .env.dist .env
docker compose up -d --build
docker compose exec php-api composer install
docker compose exec php-api bin/console doctrine:migrations:migrate
docker compose exec php-api bin/console hautelook:fixtures:load -n --purge-with-truncate

cd ../client
cp .dev.vars.dist .dev.vars
pnpm install
pnpm start
```

Le site est disponible sur [http://localhost:5173](http://localhost:5173) : on peut s'y connecter avec
`manager1@frigodons.org` (responsable) ou `volunteer1@frigodons.org` (bénévole).
