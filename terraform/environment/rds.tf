
data "aws_kms_key" "rds" {
  key_id = "alias/aws/rds"
}

data "aws_iam_role" "rds_enhanced_monitoring" {
  name = "rds-enhanced-moniroting"
}


resource "aws_db_instance" "api" {
  count                               = local.account.always_on ? 1 : 0
  identifier                          = lower("api-${local.environment}")
  name                                = "api2"
  allocated_storage                   = 10
  max_allocated_storage               = 100
  storage_type                        = "gp2"
  storage_encrypted                   = true
  skip_final_snapshot                 = local.account.skip_final_snapshot
  engine                              = "postgres"
  engine_version                      = local.account.psql_engine_version
  instance_class                      = "db.m3.medium"
  port                                = "5432"
  kms_key_id                          = data.aws_kms_key.rds.arn
  username                            = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  password                            = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  parameter_group_name                = aws_db_parameter_group.postgres-db-params.name
  vpc_security_group_ids              = [aws_security_group.rds-api.id]
  auto_minor_version_upgrade          = true
  maintenance_window                  = "sun:01:00-sun:01:30"
  multi_az                            = true
  backup_retention_period             = local.account.backup_retention_period
  deletion_protection                 = local.account.deletion_protection
  tags                                = merge(local.default_tags, local.db_component_tag)
  allow_major_version_upgrade         = true
  monitoring_interval                 = 30
  monitoring_role_arn                 = data.aws_iam_role.rds_enhanced_monitoring.arn
  enabled_cloudwatch_logs_exports     = ["postgresql", "upgrade"]
  iam_database_authentication_enabled = true
  performance_insights_enabled        = true
  performance_insights_kms_key_id     = data.aws_kms_key.rds.arn
  copy_tags_to_snapshot               = true
}

// setup a bunch of alarms that are useful for our needs
//see https://github.com/lorenzoaiello/terraform-aws-rds-alarms
// since aurora is not in use yet for pre and production,
// we'll revisit alarms as the serverless setup is different
module "aws_rds_api_alarms" {
  count                                     = local.account.always_on ? 1 : 0
  source                                    = "lorenzoaiello/rds-alarms/aws"
  version                                   = "2.1.0"
  db_instance_id                            = aws_db_instance.api[0].id
  actions_alarm                             = [data.aws_sns_topic.rds_events.arn]
  actions_ok                                = [data.aws_sns_topic.rds_events.arn]
  disk_free_storage_space_too_low_threshold = "1000000000" #configured to 1GB
  cpu_utilization_too_high_threshold        = "95"
  db_instance_class                         = "db.m3.medium"
  prefix                                    = "${local.environment}-"
  tags                                      = merge(local.default_tags, local.db_component_tag)
}

module "api_aurora" {
  auto_minor_version_upgrade    = true
  source                        = "./modules/aurora"
  count                         = local.account.aurora_enabled ? 1 : 0
  aurora_serverless             = local.account.aurora_serverless
  account_id                    = data.aws_caller_identity.current.account_id
  apply_immediately             = !local.account.deletion_protection
  cluster_identifier            = "api2"
  db_subnet_group_name          = "data-persistence-subnet-default"
  deletion_protection           = local.account.deletion_protection
  database_name                 = "api2"
  engine_version                = local.account.psql_engine_version
  environment                   = local.environment
  master_username               = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  master_password               = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  instance_count                = local.account.aurora_instance_count
  instance_class                = "db.t3.medium"
  kms_key_id                    = data.aws_kms_key.rds.arn
  replication_source_identifier = local.account.always_on ? aws_db_instance.api[0].arn : ""
  skip_final_snapshot           = !local.account.deletion_protection
  vpc_security_group_ids        = [aws_security_group.rds-api.id]
  tags                          = merge(local.default_tags, local.db_component_tag)
  copy_tags_to_snapshot         = true
}

resource "aws_db_parameter_group" "postgres-db-params" {
  name        = lower("postgres-db-params-${local.environment}")
  description = "default postgres rds parameter group"
  family      = local.account.psql_parameter_group_family
  parameter {
    name         = "log_min_duration_statement"
    value        = "500"
    apply_method = "immediate"
  }

  parameter {
    name         = "log_statement"
    value        = "all"
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
  tags                   = merge(local.default_tags, local.db_component_tag)
}

resource "aws_security_group" "rds-api" {
  name                   = "rds-api-${local.environment}"
  description            = "api rds access"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = merge(local.default_tags, local.db_component_tag)

  lifecycle {
    create_before_destroy = true
  }
}

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "rds-api" {
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds-client.id
  security_group_id        = aws_security_group.rds-api.id
}


data "aws_caller_identity" "current" {}
