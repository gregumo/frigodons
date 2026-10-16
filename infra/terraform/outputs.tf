# Displayed at the end of `terraform apply` (and with `terraform output`).

output "api_url" {
  value = "https://${cloudflare_dns_record.api.name}"
}

output "server_ip" {
  value = data.hcloud_server.api.ipv4_address
}

output "backup_bucket" {
  value = minio_s3_bucket.backup.bucket
}

# Hidden in the output of `apply`: `terraform output -raw database_password` shows it.
output "database_password" {
  value     = random_password.database.result
  sensitive = true
}
