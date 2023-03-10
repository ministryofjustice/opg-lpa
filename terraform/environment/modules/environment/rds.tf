
data "aws_kms_key" "rds" {
  key_id = "alias/aws/rds"
}

data "aws_kms_key" "multi_region_db_snapshot_key" {
  key_id = "arn:aws:kms:${local.region_name}:${var.account.account_id}:alias/mrk_db_snapshot_key-${var.account_name}"
}

data "aws_iam_role" "rds_enhanced_monitoring" {
  name = "rds-enhanced-monitoring"
}

data "aws_sns_topic" "rds_events" {
  name = "${var.account_name}-${local.region_name}-rds-events"
}

data "aws_db_snapshot" "api_snapshot" {
  count                  = !local.is_primary_region && var.account.always_on ? 1 : 0
  db_instance_identifier = lower("api-${var.environment_name}")
  most_recent            = true
}

resource "aws_db_instance" "api" {
  count                               = var.account.always_on ? 1 : 0
  identifier                          = lower("api-${var.environment_name}")
  db_name                             = "api2"
  allocated_storage                   = 10
  max_allocated_storage               = 100
  storage_type                        = "gp2"
  storage_encrypted                   = true
  skip_final_snapshot                 = var.account.skip_final_snapshot
  engine                              = "postgres"
  engine_version                      = var.account.psql_engine_version
  instance_class                      = var.account.rds_instance_type
  port                                = "5432"
  kms_key_id                          = local.is_primary_region ? data.aws_kms_key.rds.arn : data.aws_kms_key.multi_region_db_snapshot_key.arn
  username                            = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  password                            = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  parameter_group_name                = aws_db_parameter_group.postgres13-db-params.name
  vpc_security_group_ids              = [aws_security_group.rds-api.id]
  auto_minor_version_upgrade          = false
  maintenance_window                  = "wed:05:00-wed:09:00"
  multi_az                            = true
  backup_retention_period             = var.account.backup_retention_period
  deletion_protection                 = var.account.deletion_protection
  tags                                = local.db_component_tag
  allow_major_version_upgrade         = true
  monitoring_interval                 = 30
  monitoring_role_arn                 = data.aws_iam_role.rds_enhanced_monitoring.arn
  enabled_cloudwatch_logs_exports     = ["postgresql", "upgrade"]
  iam_database_authentication_enabled = true
  performance_insights_enabled        = true
  performance_insights_kms_key_id     = data.aws_kms_key.rds.arn
  copy_tags_to_snapshot               = true
  snapshot_identifier                 = !local.is_primary_region ? data.aws_db_snapshot.api_snapshot[0].id : null
}

// setup a bunch of alarms that are useful for our needs
//see https://github.com/lorenzoaiello/terraform-aws-rds-alarms
// since aurora is not in use yet for pre and production,
// we'll revisit alarms as the serverless setup is different
module "aws_rds_api_alarms" {
  count                                     = var.account.always_on ? 1 : 0
  source                                    = "lorenzoaiello/rds-alarms/aws"
  version                                   = "2.2.0"
  db_instance_id                            = aws_db_instance.api[0].id
  actions_alarm                             = [data.aws_sns_topic.rds_events.arn]
  actions_ok                                = [data.aws_sns_topic.rds_events.arn]
  disk_free_storage_space_too_low_threshold = "1000000000" #configured to 1GB
  disk_burst_balance_too_low_threshold      = "50"
  cpu_utilization_too_high_threshold        = "95"
  anomaly_band_width                        = "10"
  evaluation_period                         = "10"
  db_instance_class                         = "db.m3.medium"
  prefix                                    = "${var.environment_name}-"
  tags                                      = local.db_component_tag
}

module "api_aurora" {
  auto_minor_version_upgrade    = true
  source                        = "./modules/aurora"
  count                         = var.account.aurora_enabled ? 1 : 0
  aurora_serverless             = var.account.aurora_serverless
  account_id                    = data.aws_caller_identity.current.account_id
  apply_immediately             = !var.account.deletion_protection
  cluster_identifier            = "api2"
  db_subnet_group_name          = "data-persistence-subnet-default"
  deletion_protection           = var.account.deletion_protection
  database_name                 = "api2"
  engine_version                = var.account.psql_engine_version
  environment                   = var.environment_name
  master_username               = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  master_password               = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  instance_count                = var.account.aurora_instance_count
  instance_class                = "db.t3.medium"
  kms_key_id                    = data.aws_kms_key.rds.arn
  replication_source_identifier = var.account.always_on ? aws_db_instance.api[0].arn : ""
  skip_final_snapshot           = !var.account.deletion_protection
  vpc_security_group_ids        = [aws_security_group.rds-api.id]
  tags                          = local.db_component_tag
  copy_tags_to_snapshot         = true
}

resource "aws_db_parameter_group" "postgres13-db-params" {
  name        = lower("postgres13-db-params-${var.environment_name}")
  description = "default postgres13 rds parameter group"
  family      = var.account.psql13_parameter_group_family
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

resource "aws_security_group" "rds-client" {
  name                   = "rds-client-${var.environment_name}"
  description            = "rds access for ${var.environment_name}"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
}

resource "aws_security_group" "rds-api" {
  name                   = "rds-api-${var.environment_name}"
  description            = "api rds access"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag

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
  description              = "RDS client to RDS - Postgres"
}
