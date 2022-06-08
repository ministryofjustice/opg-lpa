data "aws_caller_identity" "current" {}

resource "aws_db_instance_automated_backups_replication" "instance" {
  source_db_instance_arn = var.source_db_instance_arn
  retention_period       = var.retention_period
  provider               = aws.destination
  kms_key_id             = data.aws_kms_key.destination_rds_snapshot_key.arn
}


data "aws_kms_key" "destination_rds_snapshot_key" {
  provider = aws.destination
  key_id   = "arn:aws:kms:${var.destination_region_name}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
}

