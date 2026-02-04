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
