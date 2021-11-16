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
  depends_on = [aws_elasticache_replication_group.front_cache]
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
  threshold                 = 50000000
  treat_missing_data        = "notBreaching"
  dimensions = {
    CacheClusterId = element(local.cache_member_clusters, count.index)
  }
  depends_on = [aws_elasticache_replication_group.front_cache]
}

resource "aws_cloudwatch_event_rule" "tasks_stopped" {
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
    local.default_tags,
    local.shared_component_tag,
    {
      "Name" = "online-lpa"
    },
  )
}

resource "aws_cloudwatch_event_target" "tasks_stopped" {
  rule      = aws_cloudwatch_event_rule.tasks_stopped.name
  target_id = "SendToSNS"
  arn       = aws_sns_topic.cloudwatch_to_account_ops_alerts.arn
}

resource "aws_sns_topic_policy" "task_stopped_policy" {
  arn    = aws_sns_topic.cloudwatch_to_account_ops_alerts.arn
  policy = data.aws_iam_policy_document.task_stopped_topic_policy.json
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
