resource "aws_cloudwatch_event_rule" "restore_job_failures" {
  name        = "${var.environment_name}-${data.aws_region.current.region}-backup-restore-failures"
  description = "Triggers on AWS Restore Job failures"

  event_pattern = jsonencode({
    source      = ["aws.backup"]
    detail-type = ["Backup Job State Change"]
    detail = {
      state = ["FAILED", "ABORTED"]
    }
  })
}

resource "aws_cloudwatch_event_target" "restore_job_failures_target" {
  rule      = aws_cloudwatch_event_rule.restore_job_failures.name
  target_id = "rds-events-sns"
  arn       = data.aws_sns_topic.rds_events.arn
}

# Restore validation- job completed rule - TO BE DISCUSSED

# resource "aws_cloudwatch_event_rule" "restore_job_completed" {
#   name        = "${var.environment_name}-${data.aws_region.current.region}-backup-restore-completed"
#   description = "Triggers on AWS Backup Restore Job completions for Valdidation checks"


#   event_pattern = jsonencode({
#     source      = ["aws.backup"]
#     detail-type = ["Backup Job State Change"]
#     detail = {
#       state = ["COMPLETED"]
#     }
#     }
#   )
# }
