data "pagerduty_service" "pagerduty" {
  name = local.pager_duty_ops_service_name
}

data "pagerduty_service" "pagerduty_db_alerts" {
  name = local.pager_duty_db_service_name
}

data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}

data "pagerduty_vendor" "custom_events" {
  name = "Custom Event Transformer"
}

resource "pagerduty_service_integration" "cloudwatch_integration" {

  name    = "${data.pagerduty_vendor.cloudwatch.name} ${local.account_name} Account Ops"
  service = data.pagerduty_service.pagerduty.id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "pagerduty_service_integration" "db_alerts_integration" {
  count   = local.account_name != "development" ? 1 : 0
  name    = "${local.account_name} Account DB Alerts"
  service = data.pagerduty_service.pagerduty_db_alerts.id
  vendor  = data.pagerduty_vendor.custom_events.id
}

resource "aws_sns_topic_subscription" "cloudwatch_elasticache_alerts_sns_subscription" {
  topic_arn              = aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration.integration_key}/enqueue"
}

resource "aws_sns_topic_subscription" "cloudwatch_account_ops_sns_subscription" {
  topic_arn              = aws_sns_topic.cloudwatch_to_account_ops_alerts.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration.integration_key}/enqueue"
}
