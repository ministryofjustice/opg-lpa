#  old kms keys  - needed until prod and preprod are migrated to new keys - used in current backup plan

data "aws_caller_identity" "current" {}

data "aws_region" "secondary" {
  provider = aws.destination
}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}

data "aws_kms_key" "destination_rds_snapshot_key" {
  provider = aws.destination
  key_id   = "arn:aws:kms:${var.destination_region_name}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
}

data "aws_kms_key" "source_rds_snapshot_key" {
  key_id = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
}

data "aws_kms_key" "backup" {
  provider = aws.backup
  key_id   = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.backup.account_id}:alias/opg-lpa-${var.account_name}-aws-backup-key"
}

#  new kms keys - used in new backup plan and vaults - to be used as default post-production module migration

data "aws_region" "replica_region" {
  provider = aws.replica
}
data "aws_kms_key" "rds_encryption_key_primary" {
  key_id = "alias/opg-lpa-${var.account_name}-rds-encryption-key"
}
data "aws_kms_key" "rds_encryption_key_replica" {
  provider = aws.replica
  key_id   = "alias/opg-lpa-${var.account_name}-rds-encryption-key"
}
data "aws_kms_key" "cross_account_backup_key" {
  provider = aws.backup
  key_id   = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.backup.account_id}:alias/opg-lpa-${var.account_name}-aws-backup-key"
}

data "aws_kms_key" "backup_source_encryption_key" {
  key_id = "alias/opg-lpa-${var.account_name}-aws-backup-source-account-key"
}

data "aws_kms_key" "backup_destination_encryption_key" {
  provider = aws.replica
  key_id   = "alias/opg-lpa-${var.account_name}-aws-backup-source-account-key"
}
