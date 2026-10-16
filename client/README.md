# Frig'o dons client

Application [React Router 7](https://reactrouter.com/) rendue côté serveur (SSR) sur
[Cloudflare Workers](https://developers.cloudflare.com/workers/), avec
[React Aria Components](https://react-spectrum.adobe.com/react-aria/components.html) et
[Tailwind CSS 4](https://tailwindcss.com/).

## Démarrer en local

L'API doit tourner (voir [`../api/README.md`](../api/README.md)).

```bash
# Créer le fichier des variables locales du Worker (adapter les valeurs si besoin)
cp .dev.vars.dist .dev.vars

pnpm install
pnpm start
```

Le site est disponible sur [http://localhost:5173](http://localhost:5173).

## Organisation

- `app/routes.ts` : les routes, en français comme sur l'ancien site (`/calendrier`, `/mes-informations`…),
  car les mails envoyés par l'API pointent vers ces adresses ;
- `app/routes/` : une page par fichier, avec son `loader` et son `action` ;
- `app/components/` : les quelques composants partagés (navigation, modale, champs de formulaire) ;
- `app/api.server.ts` : le client de l'API, appelé uniquement côté serveur ;
- `app/session.server.ts` : la session.

## Authentification

Le formulaire de connexion envoie l'adresse e-mail à `POST /login`. Le JWT renvoyé par l'API est stocké dans
un cookie de session `httpOnly` (signé avec `SESSION_SECRET`) : le navigateur n'appelle jamais l'API directement.
Si l'API répond 401 (token expiré, compte supprimé), la session est supprimée et l'utilisateur est redirigé vers
la page de connexion.

Le client transmet l'IP du visiteur à l'API dans `X-Forwarded-For`, pour la limite de tentatives de connexion.

## Variables

| Variable         | Description                                                    |
|------------------|----------------------------------------------------------------|
| `API_URL`        | URL de l'API (dans `wrangler.jsonc`, surchargée par `.dev.vars`) |
| `SESSION_SECRET` | Clé de signature du cookie de session (secret du Worker)       |

## Qualité

```bash
pnpm check      # Biome (formatage et lint)
pnpm typecheck  # Types Cloudflare, React Router et TypeScript
```

## Déploiement

La CI GitHub Actions (`.github/workflows/client.yml`) vérifie et construit le client, puis le déploie avec
`wrangler deploy` à chaque push sur `main`. Elle utilise les secrets GitHub `CLOUDFLARE_ACCOUNT_ID`,
`CLOUDFLARE_API_TOKEN` et `CLIENT_SESSION_SECRET`.
