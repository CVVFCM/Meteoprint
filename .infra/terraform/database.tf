resource "scaleway_sdb_sql_database" "database" {
    name    = "my-awesome-app-db"
    region  = "fr-par"

    min_cpu = 0
    max_cpu = 1
}
