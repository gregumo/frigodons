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

/*
 * www.frigodons.org is redirected to frigodons.org by Cloudflare, before reaching the Worker. The DNS record only needs
 * to exist and be proxied for Cloudflare to receive the requests: 100:: is a placeholder address, never contacted.
 */
resource "cloudflare_dns_record" "www" {
  count = var.client_live ? 1 : 0

  zone_id = data.cloudflare_zone.main.id
  name    = "www.${var.domain}"
  type    = "AAAA"
  content = "100::"
  proxied = true
  ttl     = 1 # Automatic
}

resource "cloudflare_ruleset" "redirects" {
  count = var.client_live ? 1 : 0

  zone_id = data.cloudflare_zone.main.id
  name    = "Redirects"
  kind    = "zone"
  phase   = "http_request_dynamic_redirect"

  rules = [{
    description = "www.${var.domain} to ${var.domain}"
    expression  = "(http.host eq \"www.${var.domain}\")"
    action      = "redirect"
    action_parameters = {
      from_value = {
        status_code           = 301
        preserve_query_string = true
        target_url = {
          expression = "concat(\"https://${var.domain}\", http.request.uri.path)"
        }
      }
    }
  }]
}

# Redirects http:// to https:// on the whole zone. api.frigodons.org is not proxied, so Caddy still handles it.
resource "cloudflare_zone_setting" "always_use_https" {
  zone_id    = data.cloudflare_zone.main.id
  setting_id = "always_use_https"
  value      = "on"
}
