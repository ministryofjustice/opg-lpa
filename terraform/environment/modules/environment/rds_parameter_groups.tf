# DB Parameter Groups

locals {
  psql_parameter_group_family_list = [
    "postgres13",
    "postgres14",
    "postgres15",
    "postgres16",
    "postgres17",
  ]
}

resource "aws_db_parameter_group" "postgres_db_params" {
  for_each    = toset(local.psql_parameter_group_family_list)
  name        = lower("${each.value}-db-params-${var.environment_name}")
  description = "default ${each.value} rds parameter group"
  family      = each.value
  parameter {
    name         = "log_min_duration_statement"
    value        = "500"
    apply_method = "immediate"
  }

  parameter {
    name         = "log_statement"
    value        = "none"
    apply_method = "immediate"
  }

  parameter {
    name         = "rds.log_retention_period"
    value        = "1440"
    apply_method = "immediate"
  }

  parameter {
    name         = "log_duration"
    value        = "1"
    apply_method = "immediate"
  }
}

resource "aws_rds_cluster_parameter_group" "postgresql_aurora_params" {
  for_each    = toset(local.psql_parameter_group_family_list)
  name        = lower("${each.value}-cluster-params-${var.environment_name}")
  description = "default ${each.value} aurora parameter group"
  family      = "aurora-postgresql${substr(each.value, -2, 2)}"
  parameter {
    name         = "log_min_duration_statement"
    value        = "500"
    apply_method = "immediate"
  }

  parameter {
    name         = "log_statement"
    value        = "none"
    apply_method = "immediate"
  }

  parameter {
    name         = "log_duration"
    value        = "1"
    apply_method = "immediate"
  }

  parameter {
    name         = "rds.log_retention_period"
    value        = "1440"
    apply_method = "immediate"
  }
}
