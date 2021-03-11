data "pagerduty_service" "pagerduty" {
  name = local.pager_duty_ops_service_name
}

data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}

resource "pagerduty_service_integration" "cloudwatch_integration" {
  count   = local.account_name == "development" ? 1 : 0
  name    = "${data.pagerduty_vendor.cloudwatch.name} ${local.account_name} Account Ops"
  service = data.pagerduty_service.pagerduty.id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "aws_sns_topic_subscription" "cloudwatch_breakglass_alerts_sns_subscription" {
  count                  = local.account_name == "development" ? 1 : 0
  topic_arn              = aws_sns_topic.cloudwatch_to_slack_breakglass_alerts.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration[0].integration_key}/enqueue"
}
