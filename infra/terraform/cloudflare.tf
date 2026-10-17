/*
 * The zone (the domain) was added by hand in Cloudflare: Terraform only reads it. The DNS records imported by
 * Cloudflare (MX, TXT...) are not managed here either: Terraform only touches what is declared in these files.
 */
data "cloudflare_zone" "main" {
  filter = {
    name = var.domain
  }
}

locals {
  cloudflare_account_id = data.cloudflare_zone.main.account.id
}

/*
 * api.frigodons.org points to the server. It is not proxied by Cloudflare: Caddy gets its own certificate.
 */
resource "cloudflare_dns_record" "api" {
  zone_id = data.cloudflare_zone.main.id
  name    = "api.${var.domain}"
  type    = "A"
  content = data.hcloud_server.api.ipv4_address
  proxied = false
  ttl     = 1 # Automatic
}

/*
 * The Worker serving the client. Terraform creates it, the CI deploys its code (`wrangler deploy`).
 * Until the domain is switched, the site is reachable on https://frigodons.<account>.workers.dev
 */
resource "cloudflare_worker" "client" {
  account_id = local.cloudflare_account_id
  name       = "frigodons"

  subdomain = {
    enabled          = true
    previews_enabled = true
  }

  # Same as client/wrangler.jsonc, otherwise each deployment of the CI and each `apply` would undo the other.
  observability = {
    enabled = true
    logs = {
      enabled         = true
      invocation_logs = true
    }
  }
}

# `count` creates the resource 0 or 1 time: the domain is only attached to the Worker when `client_live` is true.
resource "cloudflare_workers_custom_domain" "client" {
  count = var.client_live ? 1 : 0

  account_id = local.cloudflare_account_id
  zone_id    = data.cloudflare_zone.main.id
  zone_name  = data.cloudflare_zone.main.name
  hostname   = var.domain
  service    = cloudflare_worker.client.name
}

# The Worker redirects www.frigodons.org to frigodons.org (see client/workers/app.ts).
resource "cloudflare_workers_custom_domain" "client_www" {
  count = var.client_live ? 1 : 0

  account_id = local.cloudflare_account_id
  zone_id    = data.cloudflare_zone.main.id
  zone_name  = data.cloudflare_zone.main.name
  hostname   = "www.${var.domain}"
  service    = cloudflare_worker.client.name
}
