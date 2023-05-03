data "aws_cloudwatch_log_group" "cloudtrail" {
  name = "online_lpa_cloudtrail_${local.account_name}"
}

resource "aws_cloudwatch_log_metric_filter" "breakglass_metric" {
  count          = local.account.is_production ? 1 : 0
  name           = "BreakGlassConsoleLogin"
  pattern        = "{ ($.eventType = \"AwsConsoleSignIn\") && ($.userIdentity.arn = \"arn:aws:sts::${local.account_id}:assumed-role/breakglass/*\") }"
  log_group_name = data.aws_cloudwatch_log_group.cloudtrail.name

  metric_transformation {
    name      = "EventCount"
    namespace = "online-lpa/Cloudtrail"
    value     = "1"
  }
}

resource "aws_cloudwatch_metric_alarm" "account_breakglass_login_alarm" {
  count               = local.account.is_production ? 1 : 0
  actions_enabled     = true
  alarm_name          = "${local.account_name} breakglass console login check"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_slack_breakglass_alerts.arn]
  ok_actions          = [aws_sns_topic.cloudwatch_to_slack_breakglass_alerts.arn]
  alarm_description   = "number of breakglass attempts"
  namespace           = "online-lpa/Cloudtrail"
  metric_name         = "EventCount"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  period              = 60
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  statistic           = "Sum"
  tags                = local.shared_component_tag
  threshold           = 1
  treat_missing_data  = "notBreaching"
}
