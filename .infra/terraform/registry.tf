resource "scaleway_registry_namespace" "docker_registry" {
  name       = "meteoprint-registry"
  region     = "fr-par"
  project_id = scaleway_account_project.meteoprint.id

  is_public = false
}
