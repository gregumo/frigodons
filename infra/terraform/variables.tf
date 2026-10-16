# The values are set in terraform.tfvars (see terraform.tfvars.dist).

variable "domain" {
  type    = string
  default = "frigodons.org"
}

variable "client_live" {
  description = "Whether the domain is served by the Worker. Only set it to true once the old site is switched off."
  type        = bool
  default     = false
}

// Hetzner

variable "hcloud_token" {
  description = "Hetzner Cloud API token (Read & Write), from the project in the Hetzner console."
  type        = string
  sensitive   = true
}

variable "server_name" {
  description = "The name of the server, as displayed in the Hetzner console (it was created by hand)."
  type        = string
}

variable "hcloud_s3_region" {
  description = "Location of the Object Storage bucket (fsn1, nbg1 or hel1)."
  type        = string
  default     = "fsn1"
}

variable "backup_bucket" {
  description = "Name of the Object Storage bucket of the backups (unique among all Hetzner customers)."
  type        = string
  default     = "frigodons-backup"
}

variable "hcloud_s3_access_key" {
  type      = string
  sensitive = true
}

variable "hcloud_s3_secret_key" {
  type      = string
  sensitive = true
}

// Cloudflare

variable "cloudflare_api_token" {
  description = "Token used by Terraform (DNS and Workers of the zone)."
  type        = string
  sensitive   = true
}

variable "cloudflare_ci_api_token" {
  description = "Token used by the CI to deploy the Worker (\"Edit Cloudflare Workers\" template)."
  type        = string
  sensitive   = true
}

// GitHub

variable "github_token" {
  description = "Fine-grained personal access token, with the \"Secrets\" read and write permission on the repository."
  type        = string
  sensitive   = true
}

variable "github_owner" {
  type    = string
  default = "gregumo"
}

variable "github_repository" {
  type    = string
  default = "frigodons"
}

// API

variable "mailer_dsn" {
  description = "SMTP server used to send the emails, eg. smtp://user:password@smtp.example.org:587"
  type        = string
  sensitive   = true
}

variable "alert_email" {
  description = "Receives an email when a scheduled task fails (empty to disable)."
  type        = string
  default     = ""
}
