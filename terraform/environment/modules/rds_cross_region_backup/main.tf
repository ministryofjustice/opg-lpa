resource "aws_db_instance_automated_backups_replication" "default" {
  source_db_instance_arn = var.source_db_instance_arn
  kms_key_id             = var.cross_region_rds_key_arn
  retention_period       = var.retention_period
  provider               = "aws.destination"
}
