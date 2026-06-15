resource "scaleway_account_project" "meteoprint" {
  name = "meteoprint"
}

provider "scaleway" {
  zone            = "nl-ams-1"
  region          = "nl-ams"
  organization_id = "8258be04-f116-4f9a-a052-25b519c929b4"
}
