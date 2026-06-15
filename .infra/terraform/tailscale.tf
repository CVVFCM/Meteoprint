provider "tailscale" {
    tailnet = "-"
}

resource "tailscale_tailnet_key" "key" {
    reusable      = true
    ephemeral     = false
    preauthorized = true
    expiry        = 7776000
    tags          = ["tag:terraform", "tag:github"]
}
