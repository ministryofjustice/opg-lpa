

resource "aws_backup_vault_lock_configuration" "backup_account" {
  provider            = aws.backup_account
  backup_vault_name   = aws_backup_vault.backup_account
  changeable_for_days = 7
}

resource "aws_backup_vault" "backup_account" {
  provider    = aws.backup_account
  name        = "${var.environment_name}_${data.aws_region.current.region}_cross_account_backup_vault"
  kms_key_arn = aws_kms_key.backup_account_key.arn
}
# TODO = Make name identifiable to make as others using account id


resource "aws_backup_vault_policy" "backup_account" {
  provider          = aws.backup_account
  backup_vault_name = aws_backup_vault.backup_account.name
  policy            = data.aws_iam_policy_document.backup_account.json
}

data "aws_iam_policy_document" "backup_account" {
  provider = aws.backup_account
  statement {
    effect = "Allow"

    principals {
      type        = "AWS"
      identifiers = [aws_iam_role.backup_account.arn]
    }

    actions   = ["backup:CopyIntoBackupVault"]
    resources = [aws_backup_vault.backup_account.arn]
  }
}
