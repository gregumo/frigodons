terraform {
  required_version = ">= 1.10"

  # The "providers" are the plugins that talk to the APIs of each service.
  # `terraform init` downloads them, and records their exact versions in .terraform.lock.hcl.
  required_providers {
    hcloud = {
      source  = "hetznercloud/hcloud"
      version = "~> 1.68"
    }
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "5.24.0"
    }
    github = {
      source  = "integrations/github"
      version = "~> 6.6"
    }
    # Hetzner Object Storage is compatible with S3: buckets are created with the MinIO provider.
    minio = {
      source  = "aminueza/minio"
      version = "~> 3.6"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.7"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.1"
    }
  }
}
