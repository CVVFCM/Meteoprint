resource "scaleway_registry_namespace" "docker_registry" {
  name   = "meteoprint-registry"
  region = "fr-par"

  is_public = false
}
