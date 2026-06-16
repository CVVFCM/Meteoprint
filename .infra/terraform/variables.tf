variable "scaleway_organization_id" {
  description = "Scaleway organization id all projects belong to."
  type        = string
  default     = "8258be04-f116-4f9a-a052-25b519c929b4"
}

variable "scaleway_region" {
  description = "Default Scaleway region."
  type        = string
  default     = "fr-par"
}

variable "scaleway_zone" {
  description = "Default Scaleway zone."
  type        = string
  default     = "fr-par-1"
}

variable "github_owner" {
  description = "GitHub owner/organization that holds the repositories."
  type        = string
  default     = "CVVFCM"
}

variable "github_username" {
  description = "GitHub user whose SSH keys are authorized on the instances."
  type        = string
  default     = "yohang"
}

variable "tailscale_tailnet" {
  description = "Tailscale tailnet ('-' for the default of the authenticated account)."
  type        = string
  default     = "-"
}
