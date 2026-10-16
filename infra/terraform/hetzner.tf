/*
 * The server was created by hand: Terraform only reads it (a "data source"), it will never modify or delete it.
 */
data "hcloud_server" "api" {
  name = var.server_name
}

/*
 * Only SSH, HTTP and HTTPS are reachable from the internet. The database is not exposed.
 */
resource "hcloud_firewall" "api" {
  name = "frigodons-api"

  rule {
    description = "SSH"
    direction   = "in"
    protocol    = "tcp"
    port        = "22"
    source_ips  = ["0.0.0.0/0", "::/0"]
  }

  rule {
    description = "HTTP (redirection to HTTPS and certificates)"
    direction   = "in"
    protocol    = "tcp"
    port        = "80"
    source_ips  = ["0.0.0.0/0", "::/0"]
  }

  rule {
    description = "HTTPS"
    direction   = "in"
    protocol    = "tcp"
    port        = "443"
    source_ips  = ["0.0.0.0/0", "::/0"]
  }

  rule {
    description = "HTTP/3"
    direction   = "in"
    protocol    = "udp"
    port        = "443"
    source_ips  = ["0.0.0.0/0", "::/0"]
  }

  rule {
    description = "Ping"
    direction   = "in"
    protocol    = "icmp"
    source_ips  = ["0.0.0.0/0", "::/0"]
  }
}

resource "hcloud_firewall_attachment" "api" {
  firewall_id = hcloud_firewall.api.id
  server_ids  = [data.hcloud_server.api.id]
}

/*
 * The bucket receiving the backups of the database (see the pg-backup container).
 */
locals {
  s3_host = "${var.hcloud_s3_region}.your-objectstorage.com"
}

# Bucket names are shared by all Hetzner customers: `apply` fails if the name is already taken.
resource "minio_s3_bucket" "backup" {
  bucket         = var.backup_bucket
  acl            = "private"
  object_locking = true

  lifecycle {
    # `terraform destroy` will refuse to delete the backups.
    prevent_destroy = true
  }
}
