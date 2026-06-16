output "project_id" {
  description = "Scaleway project id of this deployment."
  value       = scaleway_account_project.this.id
}

output "instance_ipv6" {
  description = "Public IPv6 address of the instance."
  value       = scaleway_instance_server.front.public_ips[0].address
}

output "server_name" {
  description = "Public FQDN of the deployment."
  value       = var.server_name
}
