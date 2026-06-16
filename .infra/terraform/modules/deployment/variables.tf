variable "name" {
  description = "Deployment name, used for the Scaleway project, instance and security group."
  type        = string
  default     = "meteoprint"
}

variable "github_ssh_keys" {
  description = "SSH public keys (from the GitHub user) authorized on the instance."
  type        = list(string)
}

variable "server_name" {
  description = "Public FQDN of the deployment (Cloudflare record + Caddy SERVER_NAME)."
  type        = string
  default     = "meteoprint.cvvfcm.fr"
}

variable "cloudflare_zone_id" {
  description = "Cloudflare DNS zone id the record is created in."
  type        = string
  default     = "3fa2035c4239d02756471c8a0f51f247"
}

variable "instance_type" {
  description = "Scaleway instance commercial type."
  type        = string
  default     = "STARDUST1-S"
}

variable "instance_image" {
  description = "Scaleway instance image."
  type        = string
  default     = "debian_trixie"
}

variable "root_volume_size_gb" {
  description = "Root volume size in GB."
  type        = number
  default     = 10
}

variable "ipv6_type" {
  description = "Scaleway instance IP type."
  type        = string
  default     = "routed_ipv6"
}

variable "ssh_user" {
  description = "System user created on the instance and used for deployments."
  type        = string
  default     = "debian"
}

variable "github_repository" {
  description = "GitHub repository receiving the deploy environment."
  type        = string
  default     = "meteoprint"
}

variable "github_environment" {
  description = "GitHub Actions environment name."
  type        = string
  default     = "prod"
}

variable "app_env" {
  description = "Symfony APP_ENV exposed to the deploy workflow."
  type        = string
  default     = "prod"
}

variable "tailscale_tags" {
  description = "Tags applied to the Tailscale auth key."
  type        = list(string)
  default     = ["tag:terraform", "tag:github"]
}

variable "tailscale_key_expiry" {
  description = "Tailscale auth key expiry in seconds."
  type        = number
  default     = 7776000
}

variable "dns_proxied" {
  description = "Whether the Cloudflare record is proxied."
  type        = bool
  default     = true
}

variable "dns_ttl" {
  description = "Cloudflare record TTL (1 = automatic)."
  type        = number
  default     = 1
}

variable "additional_dns_records" {
  description = "Extra Cloudflare AAAA records pointing at the instance, keyed by FQDN."
  type        = map(object({ proxied = optional(bool, false) }))
  default     = {}
}
