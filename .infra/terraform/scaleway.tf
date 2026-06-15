resource "scaleway_account_project" "rte_production_monitor" {
  name = "meteoprint"
}

provider "scaleway" {
  zone   = "fr-par-1"
  region = "fr-par"
}

resource "scaleway_instance_ip" "v6" {
  type       = "routed_ipv6"
  project_id = scaleway_account_project.rte_production_monitor.id
}

resource "scaleway_instance_security_group" "main" {
  project_id              = scaleway_account_project.rte_production_monitor.id
  name                    = "meteoprint"
  inbound_default_policy  = "accept"
  outbound_default_policy = "accept"
}

resource "scaleway_instance_server" "front" {
  name              = "meteoprint"
  project_id        = scaleway_account_project.rte_production_monitor.id
  security_group_id = scaleway_instance_security_group.main.id
  type              = "STARDUST1-S"
  image             = "debian_trixie"

  enable_dynamic_ip = false
  ip_ids            = [scaleway_instance_ip.v6.id]

  root_volume {
    size_in_gb = 10
  }

  user_data = {
    cloud-init = templatefile("${path.module}/cloud-init.yaml.tpl", {
      deploy_public_key = tls_private_key.deploy.public_key_openssh
    })
  }
}
