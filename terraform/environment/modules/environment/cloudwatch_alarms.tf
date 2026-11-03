#5xx Alarms
resource "aws_cloudwatch_metric_alarm" "front_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX responses logged in Front for ${var.environment_name}"
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
  threshold                 = 0
  treat_missing_data        = "notBreaching"
}

# 5xx Admin Error
resource "aws_cloudwatch_metric_alarm" "admin_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX responses logged in Admin for ${var.environment_name}"
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
  threshold                 = 0
  treat_missing_data        = "notBreaching"
}

#Application 5xx Alarm
resource "aws_cloudwatch_metric_alarm" "application_5xx_errors" {
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "5xx responses logged for ${var.environment_name} (front, api, pdf or admin)"
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
  threshold                 = 0
  treat_missing_data        = "notBreaching"
}

# 4XX Alarms
resource "aws_cloudwatch_metric_alarm" "application_4xx_errors" {
  count                     = var.environment.cloudwatch_alarms.front_4xx_anomaly_alarms_enabled ? 1 : 0
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "Anomalies detected for 4xx responses logged on ${var.environment_name} (front, api, pdf or admin)"
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
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
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

# 4XX Metric Anomaly Alarm
resource "aws_cloudwatch_metric_alarm" "front_4xx_anomaly" {
  count             = var.environment.cloudwatch_alarms.front_4xx_anomaly_alarms_enabled ? 1 : 0
  alarm_name        = "${var.environment_name} public front 4XX anomaly"
  alarm_description = "Anomalies detected for 4xx responses logged in Front on ${var.environment_name}"

  evaluation_periods  = 2
  datapoints_to_alarm = 2
  threshold_metric_id = "ad1"
  comparison_operator = "GreaterThanUpperThreshold"

  insufficient_data_actions = []
  treat_missing_data        = "notBreaching"

  alarm_actions = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions    = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "4XX anomaly detection band"
    return_data = true
  }
  metric_query {
    id          = "m1"
    return_data = true
    metric {
      metric_name = "HTTPCode_Target_4XX_Count"
      namespace   = "AWS/ApplicationELB"
      period      = 60
      stat        = "Sum"
      dimensions = {
        LoadBalancer = trimprefix(split(":", aws_lb.front.arn)[5], "loadbalancer/")
      }
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "pdf_queue_excess_items" {
  alarm_name          = "${var.environment_name}-pdf-queue-excess-items"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty_ops.arn]
  alarm_description   = "More than 10 items for 5 minutes in pdf queue"
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
  count               = var.environment.cloudwatch_alarms.front_ddos_alarms_enabled ? 1 : 0
  alarm_name          = "${var.environment_name}-FrontDDoSDetected"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "3"
  metric_name         = "DDoSDetected"
  namespace           = "AWS/DDoSProtection"
  period              = "60"
  statistic           = "Average"
  threshold           = "0"
  alarm_description   = "AWS Shield Advanced has detected a DDoS attack against Front ${var.environment_name}"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty_ops.arn]
  tags                = local.front_component_tag
  dimensions = {
    ResourceArn = aws_lb.front.arn
  }
}

resource "aws_cloudwatch_metric_alarm" "admin_ddos_attack_external" {
  count               = var.environment.cloudwatch_alarms.admin_ddos_alarms_enabled ? 1 : 0
  alarm_name          = "${var.environment_name}-AdminDDoSDetected"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "3"
  metric_name         = "DDoSDetected"
  namespace           = "AWS/DDoSProtection"
  period              = "60"
  statistic           = "Average"
  threshold           = "0"
  alarm_description   = "AWS Shield Advanced has detected a DDoS attack against Admin ${var.environment_name}"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty_ops.arn]
  tags                = local.admin_component_tag
  dimensions = {
    ResourceArn = aws_lb.admin.arn
  }
}

# Auth Event Anomaly Alarms
resource "aws_cloudwatch_metric_alarm" "auth_sign_in_success_anomaly" {
  count               = var.environment.cloudwatch_alarms.auth_sign_in_success_alarms_enabled ? 1 : 0
  alarm_name          = "${var.environment_name} auth sign-in success anomaly"
  alarm_description   = "Anomaly detected on successful sign-ins for ${var.environment_name}"
  comparison_operator = "GreaterThanUpperThreshold"
  evaluation_periods  = 2
  datapoints_to_alarm = 2
  threshold_metric_id = "ad1"

  insufficient_data_actions = []
  treat_missing_data        = "notBreaching"
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "Successful Sign-ins (Expected)"
    return_data = "true"
  }

  metric_query {
    id          = "m1"
    return_data = "true"
    metric {
      metric_name = "${var.environment_name}-auth-sign-in-success"
      namespace   = "Make/Monitoring"
      period      = "120"
      stat        = "Sum"
      unit        = "Count"
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "auth_sign_in_failed_anomaly" {
  count               = var.environment.cloudwatch_alarms.auth_sign_in_failed_alarms_enabled ? 1 : 0
  alarm_name          = "${var.environment_name} auth sign-in failed anomaly"
  alarm_description   = "Anomaly detected on failed sign-ins for ${var.environment_name}"
  comparison_operator = "GreaterThanUpperThreshold"
  evaluation_periods  = 2
  datapoints_to_alarm = 2
  threshold_metric_id = "ad1"

  insufficient_data_actions = []
  treat_missing_data        = "notBreaching"
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 3)"
    label       = "Failed Sign-ins (Expected)"
    return_data = "true"
  }

  metric_query {
    id          = "m1"
    return_data = "true"
    metric {
      metric_name = "${var.environment_name}-auth-sign-in-failed"
      namespace   = "Make/Monitoring"
      period      = "120"
      stat        = "Sum"
      unit        = "Count"
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "auth_account_locked_anomaly" {
  count               = var.environment.cloudwatch_alarms.auth_account_locked_alarms_enabled ? 1 : 0
  alarm_name          = "${var.environment_name} auth account locked anomaly"
  alarm_description   = "Anomaly detected on account lockouts for ${var.environment_name}"
  comparison_operator = "GreaterThanUpperThreshold"
  evaluation_periods  = 2
  datapoints_to_alarm = 2
  threshold_metric_id = "ad1"

  insufficient_data_actions = []
  treat_missing_data        = "notBreaching"
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "Account Lockouts (Expected)"
    return_data = "true"
  }

  metric_query {
    id          = "m1"
    return_data = "true"
    metric {
      metric_name = "${var.environment_name}-auth-account-locked"
      namespace   = "Make/Monitoring"
      period      = "120"
      stat        = "Sum"
      unit        = "Count"
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "auth_sign_in_account_locked_anomaly" {
  count               = var.environment.cloudwatch_alarms.auth_sign_in_account_locked_alarms_enabled ? 1 : 0
  alarm_name          = "${var.environment_name} auth sign-in against locked account anomaly"
  alarm_description   = "Anomaly detected on sign-in attempts against locked accounts for ${var.environment_name}"
  comparison_operator = "GreaterThanUpperThreshold"
  evaluation_periods  = 2
  datapoints_to_alarm = 2
  threshold_metric_id = "ad1"

  insufficient_data_actions = []
  treat_missing_data        = "notBreaching"
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "Sign-ins Against Locked Accounts (Expected)"
    return_data = "true"
  }

  metric_query {
    id          = "m1"
    return_data = "true"
    metric {
      metric_name = "${var.environment_name}-auth-sign-in-account-locked"
      namespace   = "Make/Monitoring"
      period      = "120"
      stat        = "Sum"
      unit        = "Count"
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "auth_sign_in_inactive_account_anomaly" {
  count               = var.environment.cloudwatch_alarms.auth_sign_in_inactive_account_alarms_enabled ? 1 : 0
  alarm_name          = "${var.environment_name} auth sign-in inactive account anomaly"
  alarm_description   = "Anomaly detected on sign-in attempts against inactive accounts for ${var.environment_name}"
  comparison_operator = "GreaterThanUpperThreshold"
  evaluation_periods  = 2
  datapoints_to_alarm = 2
  threshold_metric_id = "ad1"

  insufficient_data_actions = []
  treat_missing_data        = "notBreaching"
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "Sign-ins Against Inactive Accounts (Expected)"
    return_data = "true"
  }

  metric_query {
    id          = "m1"
    return_data = "true"
    metric {
      metric_name = "${var.environment_name}-auth-sign-in-inactive-account"
      namespace   = "Make/Monitoring"
      period      = "120"
      stat        = "Sum"
      unit        = "Count"
    }
  }
}

locals {
  ecs_services = {
    front = aws_ecs_service.front.name
    api   = aws_ecs_service.api.name
    pdf   = aws_ecs_service.pdf.name
    admin = aws_ecs_service.admin.name
  }
}

resource "aws_cloudwatch_metric_alarm" "high_task_cpu" {
  for_each            = var.environment.cloudwatch_alarms.ecs_resource_alarms_enabled ? local.ecs_services : {}
  alarm_name          = "${var.environment_name}-${each.key}-high-task-cpu"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods  = 3
  threshold           = 90
  alarm_description   = "${each.key} service task has very high CPU utilisation on ${var.environment_name}"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions          = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "max_cpu"
    return_data = true
    metric {
      metric_name = "CPUUtilization"
      namespace   = "AWS/ECS"
      period      = 60
      stat        = "Maximum"
      dimensions = {
        ServiceName = each.value
        ClusterName = aws_ecs_cluster.online-lpa.name
      }
    }
  }

}

resource "aws_cloudwatch_metric_alarm" "high_task_memory" {
  for_each            = var.environment.cloudwatch_alarms.ecs_resource_alarms_enabled ? local.ecs_services : {}
  alarm_name          = "${var.environment_name}-${each.key}-high-task-memory"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods  = 3
  threshold           = 90
  alarm_description   = "${each.key} service task has very high memory utilisation on ${var.environment_name}"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions          = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "max_mem"
    return_data = true
    metric {
      metric_name = "MemoryUtilization"
      namespace   = "AWS/ECS"
      period      = 60
      stat        = "Maximum"
      dimensions = {
        ServiceName = each.value
        ClusterName = aws_ecs_cluster.online-lpa.name
      }
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "cpu_usage_anomaly" {
  for_each            = var.environment.cloudwatch_alarms.ecs_resource_alarms_enabled ? local.ecs_services : {}
  actions_enabled     = false
  alarm_name          = "${var.environment_name}-${each.key}-cpu-usage-anomaly"
  alarm_description   = "Anomaly detected for CPU utilisation of the ${each.key} service in ${var.environment_name}"
  comparison_operator = "GreaterThanUpperThreshold"
  evaluation_periods  = 3
  datapoints_to_alarm = 3
  threshold_metric_id = "ad1"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions          = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "CPU utilisation anomaly detection band"
    return_data = true
  }
  metric_query {
    id          = "m1"
    return_data = true
    metric {
      metric_name = "CPUUtilization"
      namespace   = "AWS/ECS"
      period      = 60
      stat        = "Average"
      dimensions = {
        ServiceName = each.value
        ClusterName = aws_ecs_cluster.online-lpa.name
      }
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "memory_usage_anomaly" {
  for_each            = var.environment.cloudwatch_alarms.ecs_resource_alarms_enabled ? local.ecs_services : {}
  actions_enabled     = false
  alarm_name          = "${var.environment_name}-${each.key}-memory-usage-anomaly"
  alarm_description   = "Anomaly detected for memory utilisation of the ${each.key} service in ${var.environment_name}"
  comparison_operator = "GreaterThanUpperThreshold"
  evaluation_periods  = 3
  datapoints_to_alarm = 3
  threshold_metric_id = "ad1"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions          = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  metric_query {
    id          = "ad1"
    expression  = "ANOMALY_DETECTION_BAND(m1, 2)"
    label       = "Memory utilisation anomaly detection band"
    return_data = true
  }
  metric_query {
    id          = "m1"
    return_data = true
    metric {
      metric_name = "MemoryUtilization"
      namespace   = "AWS/ECS"
      period      = 60
      stat        = "Average"
      dimensions = {
        ServiceName = each.value
        ClusterName = aws_ecs_cluster.online-lpa.name
      }
    }
  }
}
