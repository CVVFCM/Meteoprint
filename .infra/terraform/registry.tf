resource "scaleway_registry_namespace" "docker_registry" {
  name       = "meteoprint-registry"
  region     = "fr-par"
  project_id = scaleway_account_project.meteoprint.id

  is_public = false
}

resource "scaleway_iam_application" "github_cd" {
  name = "github-cd-meteoprint"
}

resource "scaleway_iam_api_key" "github_cd_key" {
  application_id = scaleway_iam_application.github_cd.id
}

resource "scaleway_iam_policy" "github_cd_policy" {
  name           = "github-cd-registry-policy"
  application_id = scaleway_iam_application.github_cd.id

  rule {
    project_ids          = [scaleway_account_project.meteoprint.id]
    permission_set_names = ["ContainerRegistryFullAccess"]
  }
}
