
resource "aws_kms_key" "backup_account_key" {
  description             = "cross account backup encryption key"
  deletion_window_in_days = 7
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.backup_account_key.json
  multi_region            = true
  provider                = aws.backup_account
}

resource "aws_kms_alias" "backup_account_key" {
  name          = "alias/mrk-rds-cross-account-backup-key"
  target_key_id = aws_kms_key.backup_account_key.key_id
  provider      = aws.backup_account
}
