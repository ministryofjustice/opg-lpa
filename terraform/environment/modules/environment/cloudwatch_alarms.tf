
#5xx Alarms
resource "aws_cloudwatch_metric_alarm" "front_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to front users for ${var.environment_name}"
  alarm_name          = "${var.environment_name} public front 5XX errors"
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
  tags                      = local.front_component_tag
  threshold                 = 2
  treat_missing_data        = "notBreaching"
}

# Metric Anomaly Alarm
resource "aws_cloudwatch_metric_alarm" "front_5xx_anomaly" {
  alarm_name                = "${var.environment_name} public front 5XX anomaly"
  comparison_operator       = "GreaterThanUpperThreshold"
  evaluation_periods        = 2
  threshold_metric_id       = "ad1"
  alarm_description         = "Anomaly detection in 5xx Errors returned to front users for ${var.environment_name}"
  datapoints_to_alarm       = 2
  insufficient_data_actions = []
  treat_missing_data        = "notBreaching"
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "5XX anomaly detection band"
    return_data = true
  }
  metric_query {
    id          = "m1"
    return_data = false
    metric {
      metric_name = "HTTPCode_Target_5XX_Count"
      namespace   = "AWS/ApplicationELB"
      period      = 60
      stat        = "Sum"
      dimensions = {
        LoadBalancer = trimprefix(split(":", aws_lb.front.arn)[5], "loadbalancer/")
      }
    }
  }
}

# 5xx Admin Error
resource "aws_cloudwatch_metric_alarm" "admin_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to admin users for ${var.environment_name}"
  alarm_name          = "${var.environment_name} admin front 5XX errors"
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
  tags                      = local.admin_component_tag
  threshold                 = 2
  treat_missing_data        = "notBreaching"
}

#Application 5xx Alarm
resource "aws_cloudwatch_metric_alarm" "application_5xx_errors" {
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "Applications are logging 500 errors for ${var.environment_name}"
  alarm_name                = "${var.environment_name} application 5XX errors"
  comparison_operator       = "GreaterThanThreshold"
  datapoints_to_alarm       = 2
  metric_name               = "${var.environment_name}-5xx-errors"
  evaluation_periods        = 2
  insufficient_data_actions = []
  namespace                 = "Make/Monitoring"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = 60
  statistic                 = "Sum"
  threshold                 = 2
  treat_missing_data        = "notBreaching"
}

# 4XX Alarms
resource "aws_cloudwatch_metric_alarm" "application_4xx_errors" {
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "Applications are logging 40x authentication errors for ${var.environment_name}"
  alarm_name                = "${var.environment_name} application 40x errors"
  comparison_operator       = "GreaterThanUpperThreshold"
  datapoints_to_alarm       = 2
  evaluation_periods        = 2
  threshold_metric_id       = "e1"
  insufficient_data_actions = []
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  treat_missing_data        = "notBreaching"

  metric_query {
    id          = "e1"
    expression  = "ANOMALY_DETECTION_BAND(m1)"
    label       = "Authentication Errors (Expected)"
    return_data = "true"
  }

  metric_query {
    id          = "m1"
    return_data = "true"
    metric {
      metric_name = "${var.environment_name}-40x-errors"
      namespace   = "Make/Monitoring"
      period      = "120"
      stat        = "Average"
      unit        = "Count"
    }
  }
}

# # 4XX Metric Anomaly Alarm
# resource "aws_cloudwatch_metric_alarm" "front_4xx_anomaly" {
#   alarm_name                = "${var.environment_name} public front 4XX anomaly"
#   comparison_operator       = "GreaterThanUpperThreshold"
#   evaluation_periods        = 2
#   threshold_metric_id       = "ad1"
#   alarm_description         = "Anomaly detection in 4XX Errors for ${var.environment_name}"
#   datapoints_to_alarm       = 2
#   insufficient_data_actions = []
#   treat_missing_data = "notBreaching"
#   alarm_actions = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
#   ok_actions    = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

#   metric_query {
#     id          = "ad1"
#     expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
#     label       = "5XX anomaly detection band"
#     return_data = true
#   }
#   metric_query {
#     id = "m1"
#     metric {
#       metric_name = "HTTPCode_Target_5XX_Count"
#       namespace   = "AWS/ApplicationELB"
#       period      = 60
#       stat        = "Average"
#       dimensions = {
#         LoadBalancer = trimprefix(split(":", aws_lb.front.arn)[5], "loadbalancer/")
#       }
#     }
#   }
# }

resource "aws_cloudwatch_metric_alarm" "pdf_queue_excess_items" {
  actions_enabled     = true
  alarm_name          = "${var.environment_name}-pdf-queue-excess-items"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty_ops.arn]
  alarm_description   = "ApproximateNumberOfMessagesVisible >= 10 for 5 minutes in pdf queue"
  namespace           = "AWS/SQS"
  metric_name         = "ApproximateNumberOfMessagesVisible"
  comparison_operator = "GreaterThanThreshold"
  dimensions = {
    QueueName = aws_sqs_queue.pdf_fifo_queue.name
  }
  ok_actions          = [aws_sns_topic.cloudwatch_to_pagerduty_ops.arn]
  period              = 60
  evaluation_periods  = 5
  datapoints_to_alarm = 5
  statistic           = "Sum"
  tags                = local.pdf_component_tag
  threshold           = 10
  treat_missing_data  = "notBreaching"
}



resource "aws_cloudwatch_metric_alarm" "front_ddos_attack_external" {
  alarm_name          = "${var.environment_name}-FrontDDoSDetected"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "3"
  metric_name         = "DDoSDetected"
  namespace           = "AWS/DDoSProtection"
  period              = "60"
  statistic           = "Average"
  threshold           = "0"
  alarm_description   = "Triggers when AWS Shield Advanced detects a DDoS attack"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty_ops.arn]
  tags                = local.front_component_tag
  dimensions = {
    ResourceArn = aws_lb.front.arn
  }
}

resource "aws_cloudwatch_metric_alarm" "admin_ddos_attack_external" {
  alarm_name          = "${var.environment_name}-AdminDDoSDetected"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "3"
  metric_name         = "DDoSDetected"
  namespace           = "AWS/DDoSProtection"
  period              = "60"
  statistic           = "Average"
  threshold           = "0"
  alarm_description   = "Triggers when AWS Shield Advanced detects a DDoS attack"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty_ops.arn]
  tags                = local.admin_component_tag
  dimensions = {
    ResourceArn = aws_lb.admin.arn
  }
}
