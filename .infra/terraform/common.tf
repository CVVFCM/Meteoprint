resource "scaleway_account_project" "meteoprint" {
  name = "meteoprint"
}

provider "scaleway" {
  zone   = "nl-ams-1"
  region = "nl-ams"
}
