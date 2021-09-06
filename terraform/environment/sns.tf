#tfsec:ignore:AWS016 - remove for now until we confirm this works with encryption
resource "aws_sns_topic" "cloudwatch_to_pagerduty" {
  name = "CloudWatch-to-PagerDuty-${local.environment}"
  tags = merge(local.default_tags, local.shared_component_tag)
}

#tfsec:ignore:AWS016 - remove for now until we confirm this works with encryption
resource "aws_sns_topic" "cloudwatch_to_pagerduty_ops" {
  name = "CloudWatch-to-PagerDuty-${local.environment}-Ops"
  tags = merge(local.default_tags, local.shared_component_tag)
}

resource "aws_sns_topic_subscription" "cloudwatch_sns_subscription" {
  topic_arn              = aws_sns_topic.cloudwatch_to_pagerduty.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration.integration_key}/enqueue"
}

resource "aws_sns_topic_subscription" "cloudwatch_sns_subscription_ops" {
  topic_arn              = aws_sns_topic.cloudwatch_to_pagerduty_ops.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration_ops.integration_key}/enqueue"
}
