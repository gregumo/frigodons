# Infrastructure

L'infrastructure de production est décrite avec [Terraform](https://developer.hashicorp.com/terraform), dans
`terraform/`.

## Terraform en quelques notions (pour qui connaît Ansible)

| Notion | Terraform | Équivalent Ansible |
|---|---|---|
| Ce qu'on décrit | L'**état voulu** de ressources (un enregistrement DNS, un bucket, un secret GitHub…) | Des **tâches** à exécuter dans l'ordre |
| Où ça agit | Les **API** des services (Hetzner, Cloudflare, GitHub) | Les **machines**, en SSH |
| Les plugins | Les **providers** (`versions.tf`), téléchargés par `terraform init` | Les modules et collections |
| Les paramètres | Les **variables** (`variables.tf`), valorisées dans `terraform.tfvars` | Les variables d'inventaire / `group_vars` |
| La mémoire | Le **state** (`terraform.tfstate`) : ce que Terraform a créé et leurs identifiants | Aucune : Ansible revérifie tout à chaque exécution |

Quelques points importants :

- **Une `resource` est gérée par Terraform** : il la crée, la modifie et peut la supprimer. **Une `data` (data source)
  est seulement lue** : le serveur Hetzner et la zone Cloudflare, créés à la main, sont des data sources, Terraform ne
  peut donc pas les modifier ni les supprimer.
- Terraform ne gère **que ce qui est déclaré** : les enregistrements DNS existants (MX, TXT…) ne sont pas touchés.
- **`terraform plan`** affiche ce qui va changer, sans rien modifier : `+` création, `~` modification, `-` suppression,
  `-/+` remplacement (suppression puis recréation). Toujours le lire avant `terraform apply`, qui demande confirmation.
- L'ordre d'exécution n'est pas celui des fichiers : Terraform le déduit des références entre ressources (ex. le secret
  GitHub `PROD_API_IP` utilise l'IP du serveur, il est donc créé après sa lecture). Tous les `.tf` d'un dossier sont lus
  ensemble, leur découpage est libre.
- La configuration du serveur (installer Docker, déposer le `.env`) est normalement le rôle d'un outil comme Ansible.
  Ici, elle est faite par des **provisioners** (`server.tf`) : des commandes lancées en SSH quand la ressource qui les
  porte est créée ou remplacée (c'est le rôle de `triggers_replace`).

## Ce que contient `terraform/`

| Fichier | Contenu |
|---|---|
| `versions.tf` | Les providers et leurs versions |
| `providers.tf` | Leur configuration (les tokens) |
| `variables.tf` | Les paramètres, à valoriser dans `terraform.tfvars` |
| `hetzner.tf` | Le serveur (lu), son pare-feu, le bucket des sauvegardes |
| `server.tf` | L'installation de Docker, la clé SSH de déploiement et le `.env` de l'API (`/root/.env`) |
| `cloudflare.tf` | La zone (lue), `api.frigodons.org`, le Worker du client et son domaine |
| `github.tf` | Les secrets utilisés par la CI |
| `outputs.tf` | Les valeurs affichées à la fin de `terraform apply` |

Les secrets de l'application (`APP_SECRET`, `JWT_SECRET`, mot de passe de la base…) sont générés par Terraform
(`random_password`) et conservés dans le state.

## Prérequis

- Terraform 1.10 ou plus, et la clé SSH qui permet de se connecter en `root` au serveur chargée dans l'agent SSH
  (`ssh-add -l` pour vérifier).
- `cp terraform/terraform.tfvars.dist terraform/terraform.tfvars`, puis le remplir :

| Variable | Où l'obtenir |
|---|---|
| `hcloud_token` | Console Hetzner Cloud → projet → Sécurité → Tokens API, en lecture et écriture |
| `server_name` | Le nom du serveur dans la console Hetzner |
| `backup_bucket` | Facultatif : le nom du bucket des sauvegardes (`frigodons-backup` par défaut), unique parmi tous les clients Hetzner |
| `hcloud_s3_access_key`, `hcloud_s3_secret_key` | Console Hetzner → projet → Object Storage → Identifiants S3 |
| `cloudflare_api_token` | Cloudflare → Mon profil → Tokens API : Zone (Lecture), DNS (Modification), Routes Workers (Modification), et Scripts Workers (Modification) sur le compte |
| `cloudflare_ci_api_token` | Le token créé avec le modèle « Edit Cloudflare Workers », pour la CI |
| `github_token` | GitHub → Settings → Developer settings → Fine-grained tokens : dépôt `frigodons`, permission « Secrets » en lecture et écriture |
| `mailer_dsn` | Le serveur SMTP qui envoie les mails, ex. `smtp://utilisateur:motdepasse@smtp.exemple.org:587` |
| `alert_email` | L'adresse qui reçoit les alertes si une tâche planifiée échoue |

## Utilisation

```bash
cd terraform
terraform init    # Télécharge les providers (une fois, ou après un changement de version)
terraform plan    # Affiche ce qui va changer
terraform apply   # Applique, après confirmation
terraform output  # Réaffiche les sorties (IP du serveur, bucket…)
```

## Accéder à la base de production

La base n'est pas exposée sur Internet : Docker la publie seulement sur `127.0.0.1:5432` du serveur, et le pare-feu
n'ouvre pas ce port. On y accède par un tunnel SSH :

```bash
ssh -N -L 5433:127.0.0.1:5432 root@<IP du serveur>   # Laisser tourner
```

Puis se connecter (TablePlus, psql…) à `127.0.0.1:5433`, base et utilisateur `frigodons`, mot de passe donné par
`terraform output -raw database_password`. TablePlus sait aussi ouvrir le tunnel lui-même (option « Over SSH »).

## Le state

`terraform.tfstate` est créé dans `terraform/` par le premier `apply`. Il n'est **pas versionné**, car il contient
tous les secrets en clair. Il faut le sauvegarder soi-même (gestionnaire de mots de passe, disque chiffré) : sans lui,
Terraform ne sait plus ce qu'il a créé et voudra tout recréer.

## Mise en production

1. `terraform apply` avec `client_live = false` : pare-feu, Docker, `.env`, bucket, `api.frigodons.org`, Worker et
   secrets GitHub. L'ancien site reste en ligne sur `frigodons.org`.
2. Merger sur `main` : la CI déploie l'API sur le serveur et le client sur `https://frigodons.<compte>.workers.dev`.
3. Restaurer une sauvegarde de l'ancienne base sur le serveur (voir `api/README.md`) et tester le site sur l'adresse
   `workers.dev`.
4. Bascule : arrêter l'ancienne application (et ses mails planifiés), restaurer une sauvegarde fraîche, supprimer
   l'enregistrement DNS de `frigodons.org` qui pointe vers l'ancien serveur, puis passer `client_live = true` et
   relancer `terraform apply`.
