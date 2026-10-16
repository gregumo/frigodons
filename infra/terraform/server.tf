/*
 * The configuration of the server. This is the part that Ansible would usually do: Terraform is not meant for it,
 * but "provisioners" can run commands over SSH when a resource is created or replaced.
 *
 * Terraform connects as root with the SSH key of your ssh-agent (`ssh-add -l` lists them).
 */
locals {
  connection = {
    type = "ssh"
    host = data.hcloud_server.api.ipv4_address
    user = "root"
  }
}

/*
 * Installs Docker, once (the provisioner only runs again if the server is replaced).
 */
resource "terraform_data" "docker" {
  triggers_replace = [data.hcloud_server.api.id]

  connection {
    type = local.connection.type
    host = local.connection.host
    user = local.connection.user
  }

  provisioner "remote-exec" {
    inline = [
      "export DEBIAN_FRONTEND=noninteractive",
      "apt-get update",
      "apt-get install -y docker.io docker-compose-v2",
      "systemctl enable --now docker",
    ]
  }
}

/*
 * The SSH key used by the CI to deploy the API. Its private part is only stored in the state and in GitHub.
 */
resource "tls_private_key" "deploy" {
  algorithm = "ED25519"
}

resource "terraform_data" "deploy_key" {
  triggers_replace = [tls_private_key.deploy.public_key_openssh]

  connection {
    type = local.connection.type
    host = local.connection.host
    user = local.connection.user
  }

  provisioner "remote-exec" {
    inline = [
      "mkdir -p ~/.ssh && touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys",
      "grep -qF '${trimspace(tls_private_key.deploy.public_key_openssh)}' ~/.ssh/authorized_keys || echo '${trimspace(tls_private_key.deploy.public_key_openssh)} github-actions-deploy' >> ~/.ssh/authorized_keys",
    ]
  }
}

/*
 * The environment of the API (see api/.env.dist), read by Docker Compose in /root, where the CI deploys.
 * When a value changes, the file is uploaded again: the API then needs to be redeployed (or `docker compose up -d`).
 */
resource "random_password" "app_secret" {
  length  = 64
  special = false
}

resource "random_password" "jwt_secret" {
  length  = 64
  special = false
}

resource "random_password" "altcha_hmac_key" {
  length  = 64
  special = false
}

resource "random_password" "database" {
  length  = 32
  special = false
}

locals {
  env_file = <<-EOT
    COMPOSE_PROJECT_NAME=frigodons
    COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml

    DOMAIN_NAME=${var.domain}

    APP_SECRET=${random_password.app_secret.result}
    JWT_SECRET=${random_password.jwt_secret.result}
    APP_URL=https://${var.domain}
    ALTCHA_HMAC_KEY=${random_password.altcha_hmac_key.result}

    DATABASE_HOST=database
    DATABASE_PORT=5432
    DATABASE_NAME=frigodons
    DATABASE_USERNAME=frigodons
    DATABASE_PASSWORD=${random_password.database.result}

    MAILER_DSN='${var.mailer_dsn}'
    MAILER_FROM=nepasrepondre@${var.domain}
    MAILER_FROM_NAME="La Cordée"
    MAILER_REPLY_TO=lacordee44@protonmail.com
    MAILER_REPLY_TO_NAME="La Cordée"
    ALERT_EMAIL=${var.alert_email}

    BACKUP_S3_HOST=${local.s3_host}
    BACKUP_S3_BUCKET=${minio_s3_bucket.backup.bucket}
    BACKUP_S3_ACCESS_KEY=${var.hcloud_s3_access_key}
    BACKUP_S3_SECRET_KEY=${var.hcloud_s3_secret_key}
  EOT
}

resource "terraform_data" "env" {
  triggers_replace = [sha256(local.env_file)]

  connection {
    type = local.connection.type
    host = local.connection.host
    user = local.connection.user
  }

  provisioner "file" {
    content     = local.env_file
    destination = "/root/.env"
  }

  provisioner "remote-exec" {
    inline = ["chmod 600 /root/.env"]
  }
}
