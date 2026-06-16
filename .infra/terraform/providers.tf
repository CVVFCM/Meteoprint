provider "scaleway" {
  zone            = var.scaleway_zone
  region          = var.scaleway_region
  organization_id = var.scaleway_organization_id
}

provider "cloudflare" {
}

provider "github" {
  owner = var.github_owner
}

provider "tailscale" {
  tailnet = var.tailscale_tailnet
}
