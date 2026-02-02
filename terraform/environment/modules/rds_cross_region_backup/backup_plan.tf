
resource "aws_backup_plan" "main" {
  name = "${var.environment_name}_aurora_backup_plan"
  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(30 19 ? * * *)" // Run at 6am UTC every day
    start_window        = 480
    target_vault_name   = aws_backup_vault.main.name
    # TODO - CHANGE BACK WHEN TEST COMPLETE
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
    copy_action {
      destination_vault_arn = aws_backup_vault.backup_account.arn
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
    copy_action {
      destination_vault_arn = aws_backup_vault.backup_account.arn
      lifecycle {
        delete_after = var.daily_backup_deletion
      }
    }
  }
}
