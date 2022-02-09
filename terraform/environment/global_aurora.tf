resource "aws_rds_global_cluster" "api" {
  count                     = local.account.aurora_global ? 1 : 0
  global_cluster_identifier = "api2-${terraform.workspace}-global"
  engine                    = "aurora-postgresql"
  engine_version            = "10.17"
  database_name             = "api2"
  storage_encrypted         = true
}
