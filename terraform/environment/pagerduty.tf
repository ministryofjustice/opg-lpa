data "pagerduty_service" "pagerduty" {
  name = local.account.pagerduty_service_name
}

data "pagerduty_service" "pagerduty_ops" {
  name = local.pager_duty_ops_service_name
}

data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}

resource "pagerduty_service_integration" "cloudwatch_integration" {
  name    = "${data.pagerduty_vendor.cloudwatch.name} ${local.environment} Environment"
  service = data.pagerduty_service.pagerduty.id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "pagerduty_service_integration" "cloudwatch_integration_ops" {
  name    = "${data.pagerduty_vendor.cloudwatch.name} ${local.environment} Environment Ops"
  service = data.pagerduty_service.pagerduty_ops.id
  vendor  = data.pagerduty_vendor.cloudwatch.id
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
