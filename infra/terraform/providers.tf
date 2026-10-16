provider "hcloud" {
  token = var.hcloud_token
}

provider "minio" {
  minio_server   = local.s3_host
  minio_region   = var.hcloud_s3_region
  minio_user     = var.hcloud_s3_access_key
  minio_password = var.hcloud_s3_secret_key
  minio_ssl      = true
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

provider "github" {
  owner = var.github_owner
  token = var.github_token
}
