#  new backup vaults with new keys - used with the new backup plan (for now) - until migration is completed and  old keys and vaults can be deleted

resource "aws_backup_vault" "backup_primary" {
  name        = "${var.environment_name}_${data.aws_region.current.region}_backup_vault_primary"
  kms_key_arn = data.aws_kms_key.backup_source_encryption_key.arn
}


resource "aws_backup_vault" "backup_replica" {
  provider    = aws.replica
  name        = "${var.environment_name}_${data.aws_region.replica_region.region}_backup_vault_replica"
  kms_key_arn = data.aws_kms_key.backup_destination_encryption_key.arn
}

resource "aws_backup_vault" "backup_cross_account" {
  provider    = aws.backup
  name        = "${var.environment_name}_${data.aws_region.current.region}_opg_lpa_backup"
  kms_key_arn = data.aws_kms_key.cross_account_backup_key.arn

}

resource "aws_backup_vault_policy" "backup_cross_account" {
  provider          = aws.backup
  backup_vault_name = aws_backup_vault.backup_cross_account.name
  policy            = data.aws_iam_policy_document.backup_cross_account_permissions.json
}

# permissions for source account to copy backups into the backup account vault
data "aws_iam_policy_document" "backup_cross_account_permissions" {
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
    resources = [aws_backup_vault.backup_cross_account.arn]
  }
}

# permissions for backup account to restore into the source account vault
data "aws_iam_policy_document" "primary_cross_account_permissions" {
  statement {
    effect = "Allow"
    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.backup.account_id}:root"]
    }
    actions   = ["backup:CopyIntoBackupVault"]
    resources = [aws_backup_vault.backup_primary.arn]
  }
}
resource "aws_backup_vault_policy" "primary_allow_cross_account" {
  count             = var.cross_account_backup_enabled ? 1 : 0
  backup_vault_name = aws_backup_vault.backup_primary.name
  policy            = data.aws_iam_policy_document.primary_cross_account_permissions.json
}
