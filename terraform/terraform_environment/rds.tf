resource "aws_db_instance" "api" {
  identifier                 = "api-${local.environment}"
  name                       = "api"
  allocated_storage          = 10
  storage_type               = "gp2"
  storage_encrypted          = true
  skip_final_snapshot        = true
  engine                     = "postgres"
  engine_version             = "9.6.11"
  instance_class             = "db.m3.medium"
  port                       = "5432"
  username                   = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  password                   = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  parameter_group_name       = aws_db_parameter_group.postgres-db-params.name
  vpc_security_group_ids     = [aws_security_group.rds-api.id]
  db_subnet_group_name       = "rds-private-subnets-dev-vpc"
  auto_minor_version_upgrade = true
  maintenance_window         = "sun:01:00-sun:01:30"
  multi_az                   = local.multi_az_db
  backup_retention_period    = local.backup_retention_period

  tags = merge(
    local.tags,
    {
      "Name" = "api.${local.environment}.${local.opg_domain}"
    },
    {
      "component" = "API"
    },
    {
      "Application" = "lpa"
    },
  )
}

resource "aws_db_parameter_group" "postgres-db-params" {
  name        = "postgres-db-params-${local.environment}"
  description = "default postgres rds parameter group"
  family      = "postgres9.6"

  parameter {
    name         = "log_min_duration_statement"
    value        = "500"
    apply_method = "immediate"
  }

  parameter {
    name         = "log_statement"
    value        = "none"
    apply_method = "pending-reboot"
  }

  parameter {
    name         = "rds.log_retention_period"
    value        = "1440"
    apply_method = "immediate"
  }
}

resource "aws_security_group" "rds-client" {
  name                   = "rds-client-${local.environment}"
  description            = "rds access for ${local.environment}"
  vpc_id                 = data.aws_vpc.vpc.id
  revoke_rules_on_delete = true

  tags = merge(
    local.tags,
    {
      "Name" = "rds-client-${local.environment}"
    },
  )

  lifecycle {
    create_before_destroy = true
  }
}

