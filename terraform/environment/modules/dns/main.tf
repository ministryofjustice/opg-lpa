
resource "aws_route53_record" "public_facing_lastingpowerofattorney" {
  provider        = aws.management
  zone_id         = data.aws_route53_zone.live_service_lasting_power_of_attorney.zone_id
  name            = "${local.dns_namespace_env_public}${local.dns_namespace_dev_prefix}${data.aws_route53_zone.live_service_lasting_power_of_attorney.name}"
  type            = "A"
  allow_overwrite = true
  alias {
    evaluate_target_health = false
    name                   = var.front_dns_name
    zone_id                = var.front_zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

# URL for the LPA Service without the www. subdomain. Redirected to the www. subdomain on front LB.
resource "aws_route53_record" "public_facing_lastingpowerofattorney_redirect" {
  count           = var.environment_name == "production" ? 1 : 0
  provider        = aws.management
  zone_id         = data.aws_route53_zone.live_service_lasting_power_of_attorney.zone_id
  name            = data.aws_route53_zone.live_service_lasting_power_of_attorney.name
  type            = "A"
  allow_overwrite = true
  alias {
    evaluate_target_health = false
    name                   = var.front_dns_name
    zone_id                = var.front_zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

//-------------------------------------------------------------
// front

resource "aws_route53_record" "front" {
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${local.dns_namespace_env}${local.dns_namespace_dev_prefix}${local.front_dns}"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = var.front_dns_name
    zone_id                = var.front_zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}


//-------------------------------------------------------------
// admin

resource "aws_route53_record" "admin" {
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${local.dns_namespace_env}${local.dns_namespace_dev_prefix}${local.admin_dns}"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = var.admin_dns_name
    zone_id                = var.admin_zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_health_check" "public_facing_lastingpowerofattorney" {
  fqdn              = aws_route53_record.public_facing_lastingpowerofattorney.fqdn
  reference_name    = "${substr(var.environment_name, 0, 20)}-lpapub"
  port              = 443
  type              = "HTTPS"
  failure_threshold = 1
  request_interval  = 30
  measure_latency   = true
  regions           = ["us-east-1", "us-west-1", "us-west-2", "eu-west-1", "ap-southeast-1", "ap-southeast-2", "ap-northeast-1", "sa-east-1"]

  provider = aws.us_east_1
}

resource "aws_cloudwatch_metric_alarm" "public_facing_lastingpowerofattorney" {
  alarm_description   = "${var.environment_name} LPA health check"
  alarm_name          = "${var.environment_name}-lpa-healthcheck-alarm"
  actions_enabled     = false
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
