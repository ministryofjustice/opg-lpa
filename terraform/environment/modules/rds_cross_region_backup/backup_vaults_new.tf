#  new backup vaults with new keys - used with the new backup plan (for now) - until migration is completed and  old keys and vaults can be deleted

resource "aws_backup_vault" "primary" {
  name        = "${var.environment_name}_${data.aws_region.current.region}_backup_vault_primary"
  kms_key_arn = data.aws_kms_key.rds_encryption_key_primary.arn
}

resource "aws_backup_vault" "replica" {
  provider    = aws.replica
  name        = "${var.environment_name}_${data.aws_region.replica_region.region}_backup_vault_replica"
  kms_key_arn = data.aws_kms_key.rds_encryption_key_replica.arn
}

resource "aws_backup_vault" "cross_account_backup" {
  provider    = aws.backup
  name        = "${var.environment_name}_${data.aws_region.current.region}_opg_lpa_backup_vault"
  kms_key_arn = data.aws_kms_key.cross_account_backup_key.arn

}

resource "aws_backup_vault_policy" "cross_account_backup" {
  provider          = aws.backup
  backup_vault_name = aws_backup_vault.cross_account_backup.name
  policy            = data.aws_iam_policy_document.cross_account_backup_permissions.json
}

data "aws_iam_policy_document" "cross_account_backup_permissions" {
  provider = aws.backup
  statement {
    effect = "Allow"

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${var.account_id}:root",
      ]
    }

    actions   = ["backup:CopyIntoBackupVault"]
    resources = [aws_backup_vault.backup_account.arn]
  }
}
