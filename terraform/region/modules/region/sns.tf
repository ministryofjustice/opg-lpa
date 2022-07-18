#tfsec:ignore:AWS016 unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_slack_elasticache_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-${local.region_name}-elasticache-alert"
  tags = local.front_component_tag
}

#tfsec:ignore:AWS016 unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_account_ops_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-${local.region_name}-ops-alert"
  tags = local.shared_component_tag
}

#tfsec:ignore:aws-sns-enable-topic-encryption - review if changed to Aurora
resource "aws_sns_topic" "rds_events" {
  name = "${local.account_name}-${local.region_name}-rds-events"
  tags = local.db_component_tag
}

#tfsec:ignore:aws-sns-enable-topic-encryption - - review if changed to Aurora
resource "aws_db_event_subscription" "rds_events" {
  name      = "${local.account_name}-${local.region_name}-rds-event-sub"
  sns_topic = aws_sns_topic.rds_events.arn
}
