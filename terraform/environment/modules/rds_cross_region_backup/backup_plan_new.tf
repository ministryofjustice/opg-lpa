#  Backup plan to be used once database migration has happened on preprod and prod for cross account backup.
# created to ensure that current vaults are not deleted when new kms keys are added to them

resource "aws_backup_selection" "new" {
  plan_id      = aws_backup_plan.new.id
  name         = "${var.environment_name}_aurora_cluster_selection_new"
  iam_role_arn = data.aws_iam_role.aurora_backup_role.arn
  resources    = [var.source_cluster_arn]
}

resource "aws_backup_plan" "new" {
  name = "${var.environment_name}_aurora_backup_plan_new"

  rule {
    completion_window   = 10080
    start_window        = 480
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(0 6 ? * * *)" // Run at 6am UTC every day
    target_vault_name   = aws_backup_vault.backup_primary.name

    lifecycle {
      cold_storage_after = var.daily_backup_cold_storage
      delete_after       = var.daily_backup_deletion
    }
    copy_action {
      destination_vault_arn = aws_backup_vault.backup_replica.arn

      lifecycle {
        delete_after = var.daily_backup_deletion
      }
    }
    dynamic "copy_action" {
      for_each = var.cross_account_backup_enabled ? [1] : []
      content {
        destination_vault_arn = aws_backup_vault.backup_cross_account.arn
        lifecycle {
          delete_after = var.daily_backup_deletion
        }
      }
    }
  }

  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "Monthly"
    schedule            = "cron(0 6 1 * ? *)" // Run at 6am UTC on the first day of every month
    start_window        = 480
    target_vault_name   = aws_backup_vault.backup_primary.name

    lifecycle {
      cold_storage_after = var.monthly_backup_cold_storage
      delete_after       = var.monthly_backup_deletion
    }
    copy_action {
      destination_vault_arn = aws_backup_vault.backup_replica.arn

      lifecycle {
        delete_after = var.monthly_backup_deletion
      }
    }
    dynamic "copy_action" {
      for_each = var.cross_account_backup_enabled ? [1] : []
      content {
        destination_vault_arn = aws_backup_vault.backup_cross_account.arn
        lifecycle {
          delete_after = var.monthly_backup_deletion
        }
      }
    }
  }
}
