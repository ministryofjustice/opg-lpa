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
  name        = lower("${each.value}-db-params")
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
  name        = lower("${each.value}-cluster-params")
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

  # for blue/green deployments
  parameter {
    name         = "apg_plan_mgmt.capture_plan_baselines"
    value        = "off"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.explain_hashes"
    value        = "0"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.log_plan_enforcement_result"
    value        = "none"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.max_databases"
    value        = "10"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.max_plans"
    value        = "10000"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.plan_capture_threshold"
    value        = "0"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.plan_hash_version"
    value        = "1"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.plan_retention_period"
    value        = "32"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.unapproved_plan_execution_threshold"
    value        = "0"
    apply_method = "immediate"
  }

  parameter {
    name         = "apg_plan_mgmt.use_plan_baselines"
    value        = "false"
    apply_method = "immediate"
  }
}
