resource "aws_sns_topic" "cloudwatch_to_pagerduty" {
  name = "CloudWatch-to-PagerDuty-${local.environment}"
  tags = merge(local.default_tags, local.shared_component_tag)
}


resource "aws_sns_topic" "cloudwatch_to_pagerduty_ops" {
  name = "CloudWatch-to-PagerDuty-${local.environment}-Ops"
  tags = merge(local.default_tags, local.shared_component_tag)
}
