resource "scaleway_account_project" "meteoprint" {
  name = "meteoprint"
}

provider "scaleway" {
  zone            = "fr-par-1"
  region          = "fr-par"
  organization_id = "8258be04-f116-4f9a-a052-25b519c929b4"
}
