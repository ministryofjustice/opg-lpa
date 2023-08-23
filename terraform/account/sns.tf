#tfsec:ignore:aws-sns-enable-topic-encryption unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_slack_elasticache_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-elasticache-alert"
  tags = local.front_component_tag
}

#tfsec:ignore:aws-sns-enable-topic-encryption unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_account_ops_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-account-ops-alert"
  tags = local.shared_component_tag
}
