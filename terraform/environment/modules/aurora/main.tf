resource "aws_rds_cluster" "cluster" {
  count                               = var.aurora_serverless ? 0 : 1
  apply_immediately                   = var.apply_immediately
  availability_zones                  = var.availability_zones
  backup_retention_period             = var.backup_retention_period
  cluster_identifier                  = "${var.cluster_identifier}-${var.environment}"
  database_name                       = var.database_name
  db_subnet_group_name                = var.db_subnet_group_name
  deletion_protection                 = var.deletion_protection
  engine                              = var.engine
  engine_version                      = var.engine_version
  enabled_cloudwatch_logs_exports     = ["postgresql"]
  final_snapshot_identifier           = "${var.database_name}-${var.environment}-final-snapshot"
  kms_key_id                          = var.kms_key_id
  master_username                     = var.master_username
  master_password                     = var.master_password
  preferred_backup_window             = "00:20-00:50"
  preferred_maintenance_window        = "sun:01:00-sun:01:30"
  replication_source_identifier       = var.replication_source_identifier
  skip_final_snapshot                 = var.skip_final_snapshot
  storage_encrypted                   = var.storage_encrypted
  vpc_security_group_ids              = var.vpc_security_group_ids
  tags                                = var.tags
  iam_database_authentication_enabled = var.iam_database_authentication_enabled
  lifecycle {
    ignore_changes  = [replication_source_identifier]
    prevent_destroy = true
  }
}

resource "aws_rds_cluster_instance" "cluster_instances" {
  count                           = var.aurora_serverless ? 0 : var.instance_count
  auto_minor_version_upgrade      = var.auto_minor_version_upgrade
  db_subnet_group_name            = var.db_subnet_group_name
  depends_on                      = [aws_rds_cluster.cluster]
  cluster_identifier              = "${var.cluster_identifier}-${var.environment}"
  copy_tags_to_snapshot           = var.copy_tags_to_snapshot
  engine                          = var.engine
  engine_version                  = var.engine_version
  identifier                      = "${var.cluster_identifier}-${var.environment}-${count.index}"
  instance_class                  = var.instance_class
  monitoring_interval             = 30
  monitoring_role_arn             = "arn:aws:iam::${var.account_id}:role/rds-enhanced-monitoring"
  performance_insights_enabled    = true
  performance_insights_kms_key_id = var.kms_key_id
  publicly_accessible             = var.publicly_accessible
  tags                            = var.tags

  timeouts {
    create = var.timeout_create
    update = var.timeout_update
    delete = var.timeout_delete
  }

  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_rds_cluster" "cluster_serverless" {
  count                        = var.aurora_serverless ? 1 : 0
  cluster_identifier           = "${var.cluster_identifier}-${var.environment}"
  apply_immediately            = var.apply_immediately
  availability_zones           = var.availability_zones
  backup_retention_period      = var.backup_retention_period
  copy_tags_to_snapshot        = var.copy_tags_to_snapshot
  database_name                = var.database_name
  db_subnet_group_name         = var.db_subnet_group_name
  deletion_protection          = var.deletion_protection
  engine                       = var.engine
  engine_mode                  = "serverless"
  final_snapshot_identifier    = "${var.database_name}-${var.environment}-final-snapshot"
  kms_key_id                   = var.kms_key_id
  master_username              = var.master_username
  master_password              = var.master_password
  preferred_backup_window      = "00:20-00:50"
  preferred_maintenance_window = "sun:01:00-sun:01:30"
  storage_encrypted            = var.storage_encrypted
  skip_final_snapshot          = var.skip_final_snapshot
  vpc_security_group_ids       = var.vpc_security_group_ids
  tags                         = var.tags

  scaling_configuration {
    auto_pause               = true
    max_capacity             = 16
    min_capacity             = 4
    seconds_until_auto_pause = 300
    timeout_action           = "ForceApplyCapacityChange"
  }
}
