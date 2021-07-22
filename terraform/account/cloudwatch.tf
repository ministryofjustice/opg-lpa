#tfsec:ignore:AWS089 - encryption costs are high.
resource "aws_cloudwatch_log_group" "online-lpa" {
  name              = "online-lpa"
  retention_in_days = local.account.retention_in_days

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    {
      "Name" = "online-lpa"
    },
  )
}

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
  tags                = merge(local.default_tags, local.shared_component_tag)
  threshold           = 1
  treat_missing_data  = "notBreaching"
}


# elasticache cloudwatch alerts
resource "aws_cloudwatch_metric_alarm" "elasticache_high_cpu_utilization" {
  for_each                  = toset(aws_elasticache_replication_group.front_cache.member_clusters)
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn]
  alarm_description         = "High CPU usage on ${lower(each.value)}"
  alarm_name                = "High CPU Utilization on ${lower(each.value)}"
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
    CacheClusterId = each.value
  }
}

resource "aws_cloudwatch_metric_alarm" "elasticache_high_swap_utilization" {
  for_each                  = toset(aws_elasticache_replication_group.front_cache.member_clusters)
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn]
  alarm_description         = "High swap mem usage on ${lower(each.value)}"
  alarm_name                = "High swap mem Utilization on ${lower(each.value)}"
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
    CacheClusterId = each.value
  }
}
