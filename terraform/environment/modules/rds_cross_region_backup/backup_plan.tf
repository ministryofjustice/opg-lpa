locals {
  cross_account_backup = var.cross_account_backup_enabled ? {
    backup_vault_arn = aws_backup_vault.backup_account.arn
  } : null
}
resource "aws_backup_plan" "main" {
  name = "${var.environment_name}_aurora_backup_plan"
  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(18 0 ? * * *)" // Run at 6am UTC every day - testing with 18:00 UTC to verify if cross account backup is
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
    dynamic "copy_action" {
      for_each = local.cross_account_backup == var.cross_account_backup_enabled ? [1] : []
      content {
        destination_vault_arn = aws_backup_vault.backup_account.arn
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
    dynamic "copy_action" {
      for_each = local.cross_account_backup == var.cross_account_backup_enabled ? [1] : []
      content {
        destination_vault_arn = aws_backup_vault.backup_account.arn
      }
    }
  }
}
