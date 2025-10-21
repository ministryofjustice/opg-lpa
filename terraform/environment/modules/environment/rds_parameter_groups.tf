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

resource "aws_db_parameter_group" "postgres-db-params" {
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

resource "aws_rds_cluster_parameter_group" "postgresql-aurora-params" {
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

##############
resource "aws_db_parameter_group" "postgres13-db-params" {
  name        = lower("postgres13-db-params-${var.environment_name}")
  description = "default postgres13 rds parameter group"
  family      = "postgres13"
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

resource "aws_db_parameter_group" "postgres14-db-params" {
  name        = lower("postgres14-db-params-${var.environment_name}")
  description = "default postgres14 rds parameter group"
  family      = "postgres14"
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

resource "aws_db_parameter_group" "postgres15-db-params" {
  name        = lower("postgres15-db-params-${var.environment_name}")
  description = "default postgres15 rds parameter group"
  family      = "postgres15"
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

resource "aws_db_parameter_group" "postgres16-db-params" {
  name        = lower("postgres16-db-params-${var.environment_name}")
  description = "default postgres16 rds parameter group"
  family      = "postgres16"
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

resource "aws_db_parameter_group" "postgres17-db-params" {
  name        = lower("postgres17-db-params-${var.environment_name}")
  description = "default postgres17 rds parameter group"
  family      = "postgres17"
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


# Cluster Parameter Groups
resource "aws_rds_cluster_parameter_group" "postgresql13-aurora-params" {
  name        = lower("postgres13-db-params-${var.environment_name}")
  description = "default postgres13 aurora parameter group"
  family      = "aurora-postgresql13"
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

resource "aws_rds_cluster_parameter_group" "postgresql14-aurora-params" {
  name        = lower("postgres14-db-params-${var.environment_name}")
  description = "default postgres14 aurora parameter group"
  family      = "aurora-postgresql14"
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

resource "aws_rds_cluster_parameter_group" "postgresql15-aurora-params" {
  name        = lower("postgresql15-db-params-${var.environment_name}")
  description = "default postgresql15 aurora parameter group"
  family      = "aurora-postgresql15"
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

resource "aws_rds_cluster_parameter_group" "postgresql16-aurora-params" {
  name        = lower("postgresql16-db-params-${var.environment_name}")
  description = "default postgresql16 aurora parameter group"
  family      = "aurora-postgresql16"
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

resource "aws_rds_cluster_parameter_group" "postgresql17-aurora-params" {
  name        = lower("postgresql17-db-params-${var.environment_name}")
  description = "default postgresql17 aurora parameter group"
  family      = "aurora-postgresql17"
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
