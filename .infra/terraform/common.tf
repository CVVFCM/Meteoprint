resource "scaleway_account_project" "meteoprint" {
  name = "meteoprint"
}

provider "scaleway" {
  zone   = "fr-par-1"
  region = "fr-par"
}
