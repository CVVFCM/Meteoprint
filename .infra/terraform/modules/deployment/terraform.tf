terraform {
  required_providers {
    scaleway = {
      source = "scaleway/scaleway"
    }
    github = {
      source = "integrations/github"
    }
    cloudflare = {
      source = "cloudflare/cloudflare"
    }
    random = {
      source = "hashicorp/random"
    }
    tls = {
      source = "hashicorp/tls"
    }
    tailscale = {
      source = "tailscale/tailscale"
    }
  }
}
