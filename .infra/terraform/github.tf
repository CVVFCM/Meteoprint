provider "github" {
  owner = "CVVFCM"
}

data "github_user" "me" {
  username = "yohang"
}

resource "random_bytes" "app_secret" {
  length = 32
}

resource "tls_private_key" "deploy" {
  algorithm = "ED25519"
}

resource "github_repository_environment" "deploy" {
  repository  = "meteoprint"
  environment = "prod"
}

resource "github_actions_environment_secret" "ssh_key" {
  repository  = "meteoprint"
  environment = github_repository_environment.deploy.environment
  secret_name = "SSH_PRIVATE_KEY"
  value       = tls_private_key.deploy.private_key_openssh
}

resource "github_actions_environment_secret" "ssh_host" {
  repository  = "meteoprint"
  environment = github_repository_environment.deploy.environment
  secret_name = "SSH_HOST"
  value       = "meteoprint"
}

resource "github_actions_environment_secret" "ssh_user" {
  repository  = "meteoprint"
  environment = github_repository_environment.deploy.environment
  secret_name = "SSH_USER"
  value       = "debian"
}

resource "github_actions_environment_secret" "app_secret" {
  repository  = "meteoprint"
  environment = github_repository_environment.deploy.environment
  secret_name = "APP_SECRET"
  value       = random_bytes.app_secret.hex
}

resource "github_actions_environment_variable" "app_env" {
  repository    = "meteoprint"
  environment   = github_repository_environment.deploy.environment
  variable_name = "APP_ENV"
  value         = "prod"
}

resource "github_actions_environment_variable" "server_name" {
  repository    = "meteoprint"
  environment   = github_repository_environment.deploy.environment
  variable_name = "SERVER_NAME"
  value         = "meteoprint.cvvfcm.fr"
}
