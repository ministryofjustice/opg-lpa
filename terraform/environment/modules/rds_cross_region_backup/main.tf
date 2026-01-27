data "aws_caller_identity" "current" {}

data "aws_region" "current" {}

data "aws_region" "secondary" {
  provider = aws.destination
}

resource "aws_backup_plan" "main" {
  name = "${var.environment_name}_aurora_backup_plan"
  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(0 6 ? * * *)" // Run at 6am UTC every day
    start_window        = 480
    target_vault_name   = aws_backup_vault.main.name

    lifecycle {
      cold_storage_after = var.daily_backup_cold_storage
      delete_after       = var.daily_backup_deletion
    }
    copy_action {
      destination_vault_arn = aws_backup_vault.secondary.arn

      lifecycle {
        delete_after = var.daily_backup_deletion
      }
    }
  }
  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "Monthly"
    schedule            = "cron(0 6 1 * ? *)" // Run at 6am UTC on the first day of every month
    start_window        = 480
    target_vault_name   = aws_backup_vault.main.name

    lifecycle {
      cold_storage_after = var.monthly_backup_cold_storage
      delete_after       = var.monthly_backup_deletion
    }
    copy_action {
      destination_vault_arn = aws_backup_vault.secondary.arn

      lifecycle {
        delete_after = var.monthly_backup_deletion
      }
    }
  }
}
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
