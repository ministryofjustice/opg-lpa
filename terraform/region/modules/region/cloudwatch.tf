locals {
  # Don't create ECS Task Stopped alerts in development account
  enable_task_stopped_alerts = var.account_name != "development"
}
resource "aws_cloudwatch_metric_alarm" "elasticache_high_cpu_utilization" {
  count                     = local.cache_cluster_count
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn]
  alarm_description         = "High CPU usage on ${element(local.cache_member_clusters, count.index)}"
  alarm_name                = "high-cpu-utilization-${element(local.cache_member_clusters, count.index)}"
  comparison_operator       = "GreaterThanThreshold"
  datapoints_to_alarm       = 2
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "CPUUtilization"
  namespace                 = "AWS/ElastiCache"
  ok_actions                = [aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn]
  period                    = "60"
  statistic                 = "Average"
  threshold                 = 90
  treat_missing_data        = "notBreaching"
  dimensions = {
    CacheClusterId = element(local.cache_member_clusters, count.index)
  }
  depends_on = [aws_elasticache_replication_group.new_front_cache]
}

resource "aws_cloudwatch_metric_alarm" "elasticache_high_swap_utilization" {
  count                     = local.cache_cluster_count
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn]
  alarm_description         = "High swap mem usage on ${element(local.cache_member_clusters, count.index)} "
  alarm_name                = "high-swap-mem-${element(local.cache_member_clusters, count.index)}}"
  comparison_operator       = "GreaterThanThreshold"
  datapoints_to_alarm       = 2
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "SwapUsage"
  namespace                 = "AWS/ElastiCache"
  ok_actions                = [aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn]
  period                    = "60"
  statistic                 = "Sum"
  threshold                 = 95000000
  treat_missing_data        = "notBreaching"
  dimensions = {
    CacheClusterId = element(local.cache_member_clusters, count.index)
  }
  depends_on = [aws_elasticache_replication_group.new_front_cache]
}

resource "aws_cloudwatch_event_rule" "tasks_stopped" {
  count       = local.enable_task_stopped_alerts ? 1 : 0
  name        = "${local.account_name}-${local.region_name}-capture-ecs-task-stopped"
  description = "Capture each task-stopped event in ECS"

  event_pattern = jsonencode({
    source = ["aws.ecs"]

    detail-type = ["ECS Task State Change"]

    detail = {
      lastStatus = ["STOPPED"]
      stopCode   = ["TaskFailedToStart"]
    }
  })
  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "online-lpa"
    },
  )
}

moved {
  from = aws_cloudwatch_event_rule.tasks_stopped
  to   = aws_cloudwatch_event_rule.tasks_stopped[0]
}

resource "aws_cloudwatch_event_target" "tasks_stopped" {
  count     = local.enable_task_stopped_alerts ? 1 : 0
  rule      = aws_cloudwatch_event_rule.tasks_stopped[0].name
  target_id = "SendToSNS"
  arn       = aws_sns_topic.cloudwatch_to_account_ops_alerts.arn
}

moved {
  from = aws_cloudwatch_event_target.tasks_stopped
  to   = aws_cloudwatch_event_target.tasks_stopped[0]
}

resource "aws_sns_topic_policy" "task_stopped_policy" {
  count  = local.enable_task_stopped_alerts ? 1 : 0
  arn    = aws_sns_topic.cloudwatch_to_account_ops_alerts.arn
  policy = data.aws_iam_policy_document.task_stopped_topic_policy.json
}

moved {
  from = aws_sns_topic_policy.task_stopped_policy
  to   = aws_sns_topic_policy.task_stopped_policy[0]
}

data "aws_iam_policy_document" "task_stopped_topic_policy" {
  statement {
    effect  = "Allow"
    actions = ["SNS:Publish"]

    principals {
      type        = "Service"
      identifiers = ["events.amazonaws.com"]
    }

    resources = [aws_sns_topic.cloudwatch_to_account_ops_alerts.arn]
  }
}

#tfsec:ignore:aws-cloudwatch-log-group-customer-key
resource "aws_cloudwatch_log_group" "online-lpa" {
  name              = "online-lpa"
  retention_in_days = local.account.retention_in_days

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "online-lpa"
    },
  )
}
