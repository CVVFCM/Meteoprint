resource "scaleway_instance_ip" "v6" {
  type       = "routed_ipv6"
  project_id = scaleway_account_project.meteoprint.id
}

resource "scaleway_instance_security_group" "main" {
  project_id              = scaleway_account_project.meteoprint.id
  name                    = "meteoprint"
  inbound_default_policy  = "accept"
  outbound_default_policy = "accept"
}

resource "scaleway_instance_server" "front" {
  name              = "meteoprint"
  project_id        = scaleway_account_project.meteoprint.id
  security_group_id = scaleway_instance_security_group.main.id
  type              = "STARDUST1-S"
  image             = "debian_trixie"

  enable_dynamic_ip = false
  ip_ids            = [scaleway_instance_ip.v6.id]

  root_volume {
    volume_type = "l_ssd"
    size_in_gb  = 10
  }

  user_data = {
    cloud-init = templatefile("${path.module}/cloud-init.yaml.tpl", {
      deploy_public_key = tls_private_key.deploy.public_key_openssh
      tailscale_key     = tailscale_tailnet_key.key.key
      github_keys       = join("\n      - ", data.github_user.me.ssh_keys)
    })
  }
}
