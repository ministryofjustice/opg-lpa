resource "aws_sns_topic" "cloudwatch_to_slack_breakglass_alerts" {
  name              = "CloudWatch-to-PagerDuty-${local.account_name}-Breakglass-alert"
  tags              = merge(local.default_tags, local.shared_component_tag)
  kms_master_key_id = "alias/aws/sns"
}

resource "aws_sns_topic" "cloudwatch_to_slack_elasticache_alerts" {
  name              = "CloudWatch-to-PagerDuty-${local.account_name}-elasticache-alert"
  tags              = merge(local.default_tags, local.front_component_tag)
  kms_master_key_id = "alias/aws/sns"
}
