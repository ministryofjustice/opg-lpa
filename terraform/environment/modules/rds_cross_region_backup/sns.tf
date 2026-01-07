data "aws_sns_topic" "rds_events" {
  name = "${var.account_name}-${data.aws_region.current.region}-rds-events"
}

resource "aws_backup_vault_notifications" "aws_backup_failures" {
  backup_vault_name   = aws_backup_vault.main.name
  sns_topic_arn       = data.aws_sns_topic.rds_events.arn
  backup_vault_events = ["BACKUP_JOB_FAILED", "COPY_JOB_FAILED"]
}

resource "aws_backup_vault_notifications" "restore_job_failures" {
  backup_vault_name   = aws_backup_vault.main.name
  sns_topic_arn       = data.aws_sns_topic.rds_events.arn
  backup_vault_events = ["RESTORE_JOB_FAILED", "RESTORE_JOB_COMPLETED"]
}

data "aws_iam_policy_document" "aws_rds_events_sns" {
  statement {
    actions = ["SNS:Publish"]
    effect  = "Allow"
    sid     = "AllowAwsBackupToPublish"
    principals {
      type        = "Service"
      identifiers = ["backup.amazonaws.com"]
    }

    resources = [data.aws_sns_topic.rds_events.arn]
  }
  statement {
    sid     = "AllowEventBridgeToPublish"
    actions = ["SNS:Publish"]
    effect  = "Allow"
    principals {
      type        = "Service"
      identifiers = ["events.amazonaws.com"]
    }
    resources = [data.aws_sns_topic.rds_events.arn]
  }
}
resource "aws_sns_topic_policy" "rds_events_policy" {
  arn    = data.aws_sns_topic.rds_events.arn
  policy = data.aws_iam_policy_document.aws_rds_events_sns.json
}
