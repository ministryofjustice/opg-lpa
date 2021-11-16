#tfsec:ignore:AWS016 unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_slack_elasticache_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-${local.region_name}-elasticache-alert"
  tags = merge(local.default_tags, local.front_component_tag)
}

#tfsec:ignore:AWS016 unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_account_ops_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-${local.region_name}-ops-alert"
  tags = merge(local.default_tags, local.shared_component_tag)
}
