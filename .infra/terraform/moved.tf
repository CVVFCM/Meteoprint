# Migrate pre-module state addresses into module.meteoprint so the existing live
# infrastructure is preserved (no destroy/recreate) after the refactor.

moved {
  from = scaleway_account_project.meteoprint
  to   = module.meteoprint.scaleway_account_project.this
}

moved {
  from = scaleway_instance_ip.v6
  to   = module.meteoprint.scaleway_instance_ip.v6
}

moved {
  from = scaleway_instance_security_group.main
  to   = module.meteoprint.scaleway_instance_security_group.main
}

moved {
  from = scaleway_instance_server.front
  to   = module.meteoprint.scaleway_instance_server.front
}

moved {
  from = tls_private_key.deploy
  to   = module.meteoprint.tls_private_key.deploy
}

moved {
  from = random_bytes.app_secret
  to   = module.meteoprint.random_bytes.app_secret
}

moved {
  from = tailscale_tailnet_key.key
  to   = module.meteoprint.tailscale_tailnet_key.key
}

moved {
  from = cloudflare_dns_record.dns
  to   = module.meteoprint.cloudflare_dns_record.dns
}

# Older names for the primary/direct records (no-op if absent from state).
moved {
  from = cloudflare_dns_record.public_dns
  to   = module.meteoprint.cloudflare_dns_record.dns
}

moved {
  from = cloudflare_dns_record.direct_dns
  to   = module.meteoprint.cloudflare_dns_record.additional["sprinto.cvvfcm.fr"]
}

moved {
  from = github_repository_environment.deploy
  to   = module.meteoprint.github_repository_environment.deploy
}

moved {
  from = github_actions_environment_secret.ssh_key
  to   = module.meteoprint.github_actions_environment_secret.ssh_key
}

moved {
  from = github_actions_environment_secret.ssh_host
  to   = module.meteoprint.github_actions_environment_secret.ssh_host
}

moved {
  from = github_actions_environment_secret.ssh_user
  to   = module.meteoprint.github_actions_environment_secret.ssh_user
}

moved {
  from = github_actions_environment_secret.app_secret
  to   = module.meteoprint.github_actions_environment_secret.app_secret
}

moved {
  from = github_actions_environment_variable.app_env
  to   = module.meteoprint.github_actions_environment_variable.app_env
}

moved {
  from = github_actions_environment_variable.server_name
  to   = module.meteoprint.github_actions_environment_variable.server_name
}
