
data "aws_region" "current" {}
resource "aws_backup_vault" "main" {
  name        = "${var.environment_name}_${data.aws_region.current.region}_aurora_backup_vault"
  kms_key_arn = data.aws_kms_key.source_rds_snapshot_key.arn
}

resource "aws_backup_vault" "secondary" {
  provider    = aws.destination
  name        = "${var.environment_name}_${data.aws_region.secondary.region}_aurora_backup_vault"
  kms_key_arn = data.aws_kms_key.destination_rds_snapshot_key.arn
}

resource "aws_backup_vault" "backup_account" {
  provider    = aws.backup
  name        = "${var.environment_name}_${data.aws_region.current.region}_opg_lpa"
  kms_key_arn = data.aws_kms_key.backup.arn
}

resource "aws_backup_vault_policy" "backup_account" {
  provider          = aws.backup
  backup_vault_name = aws_backup_vault.backup_account.name
  policy            = data.aws_iam_policy_document.cross_account_permissions.json
}

data "aws_iam_policy_document" "cross_account_permissions" {
  provider = aws.backup
  statement {
    effect = "Allow"

    principals {
      type        = "AWS"
      identifiers = [data.aws_iam_role.aurora_backup_role.arn]
    }

    actions   = ["backup:CopyIntoBackupVault"]
    resources = [aws_backup_vault.backup_account.arn]
  }
}

resource "aws_backup_selection" "main" {
  plan_id      = aws_backup_plan.main.id
  name         = "${var.environment_name}_aurora_cluster_selection"
  iam_role_arn = data.aws_iam_role.aurora_backup_role.arn
  resources    = [var.source_cluster_arn]
}
