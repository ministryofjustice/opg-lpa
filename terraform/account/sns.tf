#tfsec:ignore:AWS016 fix for now, to be followed with a CMK KMS
resource "aws_sns_topic" "cloudwatch_to_slack_breakglass_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-Breakglass-alert"
  tags = merge(local.default_tags, local.shared_component_tag)
}
#tfsec:ignore:AWS016 unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_slack_elasticache_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-elasticache-alert"
  tags = merge(local.default_tags, local.front_component_tag)
}

#tfsec:ignore:AWS016 unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_account_ops_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-account-ops-alert"
  tags = merge(local.default_tags, local.shared_component_tag)
}

resource "aws_sns_topic" "rds_events" {
  name = "${local.account_name}-rds-events"
  tags = merge(local.default_tags, local.db_component_tag)
}

resource "aws_db_event_subscription" "rds_events" {
  name      = "${local.account_name}-rds-event-sub"
  sns_topic = aws_sns_topic.rds_events.arn
}
