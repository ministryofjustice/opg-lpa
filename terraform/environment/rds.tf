resource "aws_db_instance" "api" {
  identifier                 = lower("api-${local.environment}")
  name                       = "api2"
  allocated_storage          = 10
  storage_type               = "gp2"
  storage_encrypted          = true
  skip_final_snapshot        = local.account.skip_final_snapshot
  engine                     = "postgres"
  engine_version             = "9.6.15"
  instance_class             = "db.m3.medium"
  port                       = "5432"
  username                   = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  password                   = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  parameter_group_name       = aws_db_parameter_group.postgres-db-params.name
  vpc_security_group_ids     = [aws_security_group.rds-api.id]
  auto_minor_version_upgrade = true
  maintenance_window         = "sun:01:00-sun:01:30"
  multi_az                   = true
  backup_retention_period    = local.account.backup_retention_period
  deletion_protection        = local.account.prevent_db_destroy
  tags                       = local.default_tags
}

resource "aws_db_parameter_group" "postgres-db-params" {
  name        = lower("postgres-db-params-${local.environment}")
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
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.default_tags
}

resource "aws_security_group" "rds-api" {
  name                   = "rds-api-${local.environment}"
  description            = "api rds access"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.default_tags

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "rds-api" {
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds-client.id
  security_group_id        = aws_security_group.rds-api.id
}
