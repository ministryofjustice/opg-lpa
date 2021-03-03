resource "aws_sns_topic" "cloudwatch_to_slack_breakglass_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-Breakglass-alert"
}
