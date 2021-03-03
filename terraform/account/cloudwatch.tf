resource "aws_cloudwatch_log_group" "online-lpa" {
  name              = "online-lpa"
  retention_in_days = local.account.retention_in_days

  tags = merge(
    local.default_tags,
    {
      "Name" = "online-lpa"
    },
  )
}

data "aws_cloudwatch_log_group" "cloudtrail" {
  name = "online_lpa_cloudtrail_${local.account_name}"
}

resource "aws_cloudwatch_log_metric_filter" "breakglass_metric" {
  name           = "BreakGlassConsoleLogin"
  pattern        = "{ $.userIdentity.arn = \"arn:aws:sts::${local.account_id}:assumed-role/breakglass/*\"  && $.eventType = \"AwsConsoleSignIn\" }"
  log_group_name = data.aws_cloudwatch_log_group.cloudtrail.name

  metric_transformation {
    name      = "EventCount"
    namespace = "online-lpa/Cloudtrail"
    value     = "1"
  }
}


resource "aws_cloudwatch_metric_alarm" "account_breakglass_login_alarm" {
  actions_enabled     = true
  alarm_name          = "${local.environment} breakglass console login check"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_slack_breakglass_alerts.arn]
  alarm_description   = "number of breakglass attempts"
  namespace           = "online-lpa/Cloudtrail"
  metric_name         = "BreakGlassConsoleLogin"
  comparison_operator = "GreaterThanThreshold"
  ok_actions          = [aws_sns_topic.cloudwatch_to_slack_breakglass_alerts.arn]
  period              = 60
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  statistic           = "Sum"
  tags                = {}
  threshold           = 1
  treat_missing_data  = "notBreaching"
}
