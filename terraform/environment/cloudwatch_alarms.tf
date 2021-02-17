
resource "aws_cloudwatch_metric_alarm" "front_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to front users for ${local.environment}"
  alarm_name          = "${local.environment} public front 5XX errors"
  comparison_operator = "GreaterThanThreshold"
  datapoints_to_alarm = 2
  dimensions = {
    "LoadBalancer" = trimprefix(split(":", aws_lb.front.arn)[5], "loadbalancer/")
  }
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "HTTPCode_Target_5XX_Count"
  namespace                 = "AWS/ApplicationELB"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = 60
  statistic                 = "Sum"
  tags                      = {}
  threshold                 = 2
  treat_missing_data        = "notBreaching"
}


resource "aws_cloudwatch_metric_alarm" "admin_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to admin users for ${local.environment}"
  alarm_name          = "${local.environment} admin front 5XX errors"
  comparison_operator = "GreaterThanThreshold"
  datapoints_to_alarm = 2
  dimensions = {
    "LoadBalancer" = trimprefix(split(":", aws_lb.admin.arn)[5], "loadbalancer/")
  }
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "HTTPCode_Target_5XX_Count"
  namespace                 = "AWS/ApplicationELB"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = 60
  statistic                 = "Sum"
  tags                      = {}
  threshold                 = 2
  treat_missing_data        = "notBreaching"
}

resource "aws_cloudwatch_log_metric_filter" "csrf_mistmatch_filter" {
  name           = "CSRFValuesMismatch"
  pattern        = "{($.priorityName = \"ERR\") && ($.message = \"Mismatched CSRF provided\")}"
  log_group_name = data.aws_cloudwatch_log_group.online-lpa.name

  metric_transformation {
    name      = "EventCount"
    namespace = "online-lpa/Cloudwatch"
    value     = "1"
  }
}

resource "aws_cloudwatch_metric_alarm" "front_csrf_mismatch_errors" {
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "CSRF Errors returned to front users for ${local.environment}"
  alarm_name                = "${local.environment} public front CSRF errors"
  comparison_operator       = "GreaterThanThreshold"
  datapoints_to_alarm       = 2
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "${data.aws_cloudwatch_log_group.online-lpa.name}:csrf_mistmatch_filter"
  namespace                 = "online-lpa/Cloudwatch"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = 60
  statistic                 = "Sum"
  tags                      = {}
  threshold                 = 2
  treat_missing_data        = "notBreaching"
}