data "aws_caller_identity" "current" {}
data "aws_region" "replica_region" {
  provider = aws.replica
}
data "aws_caller_identity" "backup" {
  provider = aws.backup
}
data "aws_kms_key" "rds_encryption_key_primary" {
  key_id = "alias/opg-lpa-${var.account_name}-rds-encryption-key"
}
data "aws_kms_key" "rds_encryption_key_replica" {
  provider = aws.replica
  key_id   = "arn:aws:kms:${data.aws_region.replica_region}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
}
data "aws_kms_key" "cross_account_backup_key" {
  provider = aws.backup
  key_id   = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.backup.account_id}:alias/${var.key_alias}"
}
