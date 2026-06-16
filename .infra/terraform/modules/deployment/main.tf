resource "scaleway_account_project" "this" {
  name = var.name
}

resource "scaleway_instance_ip" "v6" {
  type       = var.ipv6_type
  project_id = scaleway_account_project.this.id
}

resource "scaleway_instance_security_group" "main" {
  project_id              = scaleway_account_project.this.id
  name                    = var.name
  inbound_default_policy  = "accept"
  outbound_default_policy = "accept"
}

resource "scaleway_instance_server" "front" {
  name              = var.name
  project_id        = scaleway_account_project.this.id
  security_group_id = scaleway_instance_security_group.main.id
  type              = var.instance_type
  image             = var.instance_image

  enable_dynamic_ip = false
  ip_ids            = [scaleway_instance_ip.v6.id]

  root_volume {
    size_in_gb = var.root_volume_size_gb
  }

  user_data = {
    cloud-init = templatefile("${path.module}/cloud-init.yaml.tpl", {
      ssh_user          = var.ssh_user
      deploy_public_key = tls_private_key.deploy.public_key_openssh
      tailscale_key     = tailscale_tailnet_key.key.key
      github_keys       = join("\n      - ", var.github_ssh_keys)
    })
  }
}

resource "tls_private_key" "deploy" {
  algorithm = "ED25519"
}

resource "random_bytes" "app_secret" {
  length = 32
}

resource "tailscale_tailnet_key" "key" {
  reusable      = true
  ephemeral     = false
  preauthorized = true
  expiry        = var.tailscale_key_expiry
  tags          = var.tailscale_tags
}

resource "cloudflare_dns_record" "dns" {
  zone_id = var.cloudflare_zone_id
  name    = var.server_name
  type    = "AAAA"
  content = scaleway_instance_server.front.public_ips[0].address
  ttl     = var.dns_ttl
  proxied = var.dns_proxied
}

resource "github_repository_environment" "deploy" {
  repository  = var.github_repository
  environment = var.github_environment
}

resource "github_actions_environment_secret" "ssh_key" {
  repository  = var.github_repository
  environment = github_repository_environment.deploy.environment
  secret_name = "SSH_PRIVATE_KEY"
  value       = tls_private_key.deploy.private_key_openssh
}

resource "github_actions_environment_secret" "ssh_host" {
  repository  = var.github_repository
  environment = github_repository_environment.deploy.environment
  secret_name = "SSH_HOST"
  value       = var.name
}

resource "github_actions_environment_secret" "ssh_user" {
  repository  = var.github_repository
  environment = github_repository_environment.deploy.environment
  secret_name = "SSH_USER"
  value       = var.ssh_user
}

resource "github_actions_environment_secret" "app_secret" {
  repository  = var.github_repository
  environment = github_repository_environment.deploy.environment
  secret_name = "APP_SECRET"
  value       = random_bytes.app_secret.hex
}

resource "github_actions_environment_variable" "app_env" {
  repository    = var.github_repository
  environment   = github_repository_environment.deploy.environment
  variable_name = "APP_ENV"
  value         = var.app_env
}

resource "github_actions_environment_variable" "server_name" {
  repository    = var.github_repository
  environment   = github_repository_environment.deploy.environment
  variable_name = "SERVER_NAME"
  value         = var.server_name
}
