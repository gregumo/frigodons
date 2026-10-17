/*
 * The secrets used by the GitHub Actions workflows (.github/workflows/api.yml and client.yml).
 */
resource "github_actions_secret" "prod_api_ip" {
  repository  = var.github_repository
  secret_name = "PROD_API_IP"
  value       = data.hcloud_server.api.ipv4_address
}

resource "github_actions_secret" "prod_api_private_key" {
  repository  = var.github_repository
  secret_name = "PROD_API_PRIVATE_KEY"
  value       = tls_private_key.deploy.private_key_openssh
}

resource "github_actions_secret" "cloudflare_account_id" {
  repository  = var.github_repository
  secret_name = "CLOUDFLARE_ACCOUNT_ID"
  value       = local.cloudflare_account_id
}

resource "github_actions_secret" "cloudflare_api_token" {
  repository  = var.github_repository
  secret_name = "CLOUDFLARE_API_TOKEN"
  value       = var.cloudflare_ci_api_token
}

resource "random_password" "client_session_secret" {
  length  = 64
  special = false
}

resource "github_actions_secret" "client_session_secret" {
  repository  = var.github_repository
  secret_name = "CLIENT_SESSION_SECRET"
  value       = random_password.client_session_secret.result
}
