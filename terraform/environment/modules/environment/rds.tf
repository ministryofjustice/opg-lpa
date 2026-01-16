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

# TODO: control which cluster identifier to use with var.account.firewalled_networks_enabled

resource "aws_db_instance" "api" {
  count                               = var.account.always_on ? 1 : 0
  identifier                          = lower("api-${var.environment_name}")
  db_name                             = "api2"
  allocated_storage                   = 10
  max_allocated_storage               = 100
  storage_type                        = "gp2"
  storage_encrypted                   = true
  skip_final_snapshot                 = var.account.database.skip_final_snapshot
  engine                              = "postgres"
  engine_version                      = var.account.database.psql_engine_version
  instance_class                      = var.account.database.rds_instance_type
  port                                = "5432"
  kms_key_id                          = local.is_primary_region ? data.aws_kms_key.rds.arn : data.aws_kms_key.multi_region_db_snapshot_key.arn
  username                            = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  password                            = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  parameter_group_name                = data.aws_db_parameter_group.postgres_db_params[var.account.database.psql_parameter_group_family].name
  vpc_security_group_ids              = [local.rds_api_sg_id]
  auto_minor_version_upgrade          = false
  maintenance_window                  = "wed:05:00-wed:09:00"
  multi_az                            = true
  deletion_protection                 = var.account.database.deletion_protection
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
  version                                   = "2.4.1"
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
  auto_minor_version_upgrade      = true
  source                          = "./modules/aurora"
  count                           = var.account.database.aurora_enabled ? 1 : 0
  aurora_serverless               = var.account.database.aurora_serverless
  account_id                      = data.aws_caller_identity.current.account_id
  availability_zones              = data.aws_availability_zones.aws_zones.names
  apply_immediately               = !var.account.database.deletion_protection
  cluster_identifier              = var.account.database.cluster_identifier
  db_subnet_group_name            = local.db_subnet_group_name
  deletion_protection             = var.account.database.deletion_protection
  engine_version                  = var.account.database.psql_engine_version
  environment                     = var.environment_name
  aws_rds_cluster_parameter_group = data.aws_rds_cluster_parameter_group.postgresql_aurora_params[var.account.database.psql_parameter_group_family].name
  master_username                 = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  master_password                 = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  instance_count                  = var.account.database.aurora_instance_count
  instance_class                  = "db.t3.medium"
  kms_key_id                      = data.aws_kms_key.rds.arn
  replication_source_identifier   = var.account.always_on ? aws_db_instance.api[0].arn : ""
  skip_final_snapshot             = !var.account.database.deletion_protection
  vpc_security_group_ids          = [local.rds_api_sg_id]
  tags                            = local.db_component_tag
  copy_tags_to_snapshot           = true
}

resource "aws_security_group" "rds-client-old" {
  name_prefix            = "rds-client-${var.environment_name}"
  description            = "rds access for ${var.environment_name}"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group" "rds-api-old" {
  name_prefix            = "rds-api-${var.environment_name}"
  description            = "api rds access"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "rds-api-old" {
  count                    = var.account.database.rds_proxy_routing_enabled ? 0 : 1
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds-client-old.id
  security_group_id        = aws_security_group.rds-api-old.id
  description              = "RDS client to RDS - Postgres"
}

resource "aws_security_group" "rds_client" {
  name_prefix            = "rds-client-${var.environment_name}"
  description            = "rds access for ${var.environment_name}"
  vpc_id                 = data.aws_vpc.main.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group" "rds_api" {
  name_prefix            = "rds-api-${var.environment_name}"
  description            = "api rds access"
  vpc_id                 = data.aws_vpc.main.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "rds_api" {
  count                    = var.account.database.rds_proxy_routing_enabled ? 0 : 1
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds_client.id
  security_group_id        = aws_security_group.rds_api.id
  description              = "RDS client to RDS - Postgres"
}
