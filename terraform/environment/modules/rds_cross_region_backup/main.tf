data "aws_caller_identity" "current" {}

data "aws_region" "current" {}

data "aws_region" "secondary" {
  provider = aws.destination
}

resource "aws_backup_plan" "main" {
  name = "${var.environment_name}_aurora_backup_plan"

  rule {
    rule_name         = "DailyBackups"
    target_vault_name = aws_backup_vault.main.name
    schedule          = "cron(0 06 * * ? *)" // Run at 6am UTC every day

    lifecycle {
      delete_after = var.aurora_daily_backup_retention_in_days
    }

    copy_action {
      destination_vault_arn = aws_backup_vault.secondary.arn

      lifecycle {
        delete_after = var.aurora_daily_backup_retention_in_days
      }
    }
  }
  rule {
    rule_name         = "MonthlyBackups"
    target_vault_name = aws_backup_vault.main.name
    schedule          = "cron(0 61 * ? *)" // Run at 6am UTC first day of every month

    lifecycle {
      delete_after = var.aurora_daily_backup_retention_in_days
    }

    copy_action {
      destination_vault_arn = aws_backup_vault.secondary.arn

      lifecycle {
        delete_after = var.aurora_monthly_backup_retention_in_days
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
