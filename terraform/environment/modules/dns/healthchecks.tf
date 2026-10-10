resource "aws_sns_topic" "service_health_checks_global" {
  name = "${var.environment_name}-service-health-checks"
  # kms_master_key_id = data.aws_kms_alias.sns_kms_key_alias_global.target_key_id
  provider = aws.us_east_1
}

resource "aws_sns_topic" "dependencies_health_checks_global" {
  name = "${var.environment_name}-dependencies-health-checks"
  # kms_master_key_id = data.aws_kms_alias.sns_kms_key_alias_global.target_key_id
  provider = aws.us_east_1
}

resource "aws_route53_health_check" "public_facing_lastingpowerofattorney" {
  fqdn              = aws_route53_record.public_facing_lastingpowerofattorney.fqdn
  reference_name    = "${substr(var.environment_name, 0, 20)}-lpapub"
  port              = 443
  type              = "HTTPS"
  failure_threshold = 1
  request_interval  = 30
  measure_latency   = true
  regions           = ["us-east-1", "eu-west-1", "us-west-2"]

  provider = aws.us_east_1
}

resource "aws_cloudwatch_metric_alarm" "public_facing_lastingpowerofattorney" {
  count = var.environment_name == "production" ? 1 : 0

  alarm_description   = "${var.environment_name} LPA health check"
  alarm_name          = "${var.environment_name}-lpa-healthcheck-alarm"
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.service_health_checks_global.arn]
  ok_actions          = [aws_sns_topic.service_health_checks_global.arn]
  treat_missing_data  = "notBreaching"
  comparison_operator = "LessThanThreshold"
  datapoints_to_alarm = 1
  evaluation_periods  = 1
  metric_name         = "HealthCheckStatus"
  namespace           = "AWS/Route53"
  period              = 60
  statistic           = "Minimum"
  threshold           = 1
  dimensions = {
    HealthCheckId = aws_route53_health_check.public_facing_lastingpowerofattorney.id
  }

  provider = aws.us_east_1
}

resource "aws_route53_health_check" "service_health_check" {
  fqdn              = aws_route53_record.front.fqdn
  reference_name    = "${substr(var.environment_name, 0, 20)}-service"
  port              = 443
  type              = "HTTPS"
  failure_threshold = 1
  request_interval  = 30
  resource_path     = "/health-check/service"
  measure_latency   = true
  regions           = ["us-east-1", "eu-west-1", "us-west-2"]
  tags = {
    Name = "${var.environment_name} service health check"
  }
  provider = aws.us_east_1
}

resource "aws_cloudwatch_metric_alarm" "service_health_check" {
  alarm_description   = "${var.environment_name} service health check for"
  alarm_name          = "${var.environment_name}-service-health-check-alarm"
  alarm_actions       = [aws_sns_topic.service_health_checks_global.arn]
  ok_actions          = [aws_sns_topic.service_health_checks_global.arn]
  actions_enabled     = true
  comparison_operator = "LessThanThreshold"
  datapoints_to_alarm = 1
  evaluation_periods  = 1
  metric_name         = "HealthCheckStatus"
  namespace           = "AWS/Route53"
  period              = 60
  statistic           = "Minimum"
  threshold           = 1
  dimensions = {
    HealthCheckId = aws_route53_health_check.service_health_check.id
  }

  provider = aws.us_east_1
}

resource "aws_route53_health_check" "dependencies_health_check" {
  fqdn              = aws_route53_record.front.fqdn
  reference_name    = "${substr(var.environment_name, 0, 20)}-dependencies"
  port              = 443
  type              = "HTTPS"
  failure_threshold = 1
  request_interval  = 30
  resource_path     = "/health-check/dependencies"
  measure_latency   = true
  regions           = ["us-east-1", "eu-west-1", "us-west-2"]
  tags = {
    Name = "${var.environment_name} dependencies health check"
  }
  provider = aws.us_east_1
}

resource "aws_cloudwatch_metric_alarm" "dependencies_health_check" {
  alarm_description   = "${var.environment_name} dependencies health check for"
  alarm_name          = "${var.environment_name}-dependencies-health-check-alarm"
  alarm_actions       = [aws_sns_topic.dependencies_health_checks_global.arn]
  ok_actions          = [aws_sns_topic.dependencies_health_checks_global.arn]
  actions_enabled     = true
  comparison_operator = "LessThanThreshold"
  datapoints_to_alarm = 1
  evaluation_periods  = 1
  metric_name         = "HealthCheckStatus"
  namespace           = "AWS/Route53"
  period              = 60
  statistic           = "Minimum"
  threshold           = 1
  dimensions = {
    HealthCheckId = aws_route53_health_check.dependencies_health_check.id
  }

  provider = aws.us_east_1
}
