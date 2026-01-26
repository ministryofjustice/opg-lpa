data "aws_caller_identity" "current" {}

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

resource "aws_backup_selection" "main" {
  plan_id      = aws_backup_plan.main.id
  name         = "${var.environment_name}_aurora_cluster_selection"
  iam_role_arn = aws_iam_role.aurora_backup_role.arn
  resources    = [var.source_cluster_arn]
}
