resource "scaleway_sdb_sql_database" "database" {
  name       = "meteoprint"
  region     = "fr-par"
  project_id = scaleway_account_project.meteoprint.id

  min_cpu = 0
  max_cpu = 1
}
