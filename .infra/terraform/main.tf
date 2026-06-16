data "github_user" "me" {
  username = var.github_username
}

# One deployment. Add another by copying this block and overriding `name`,
# `server_name`, `cloudflare_zone_id`, `instance_type`, … (see modules/deployment/variables.tf).
module "meteoprint" {
  source = "./modules/deployment"

  github_ssh_keys = data.github_user.me.ssh_keys
  # All other inputs default to the current meteoprint values.
}
