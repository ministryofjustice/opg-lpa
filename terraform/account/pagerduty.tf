data "pagerduty_service" "pagerduty" {
  name = local.pager_duty_ops_service_name
}

data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}

resource "pagerduty_service_integration" "cloudwatch_integration" {
  name    = "${data.pagerduty_vendor.cloudwatch.name} ${local.account_name} Account Ops"
  service = data.pagerduty_service.pagerduty.id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "aws_sns_topic_subscription" "cloudwatch_breakglass_alerts_sns_subscription" {
  topic_arn              = aws_sns_topic.cloudwatch_to_slack_breakglass_alerts.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration.integration_key}/enqueue"
}
