# DB Parameter Groups

locals {
  psql_parameter_group_family_list = [
    "postgres13",
    "postgres14",
    # "postgres15",
    # "postgres16",
    # "postgres17",
  ]
}
# data "aws_db_parameter_group" "postgres_db_params" {
#   for_each = toset(local.psql_parameter_group_family_list)
#   name     = lower("${each.value}-db-params")
# }

data "aws_rds_cluster_parameter_group" "postgresql_aurora_params" {
  for_each = toset(local.psql_parameter_group_family_list)
  name     = lower("${each.value}-cluster-params")
}
