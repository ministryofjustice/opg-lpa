# data source for production04 load balancer
data "aws_elb" "old_production04_front" {
  name = "front2-production04"
}

output "old_production04_front" {
  value = data.aws_elb.old_production04_front
}

# data source for new production application load balancer
data "aws_lb" "new_lpa_production_front" {
  provider = aws.new_lpa_prod_ecs
  name     = "production-front"
}

resource "aws_route53_record" "lastingpowerofattorney_service_gov_uk" {
  zone_id = "${data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.zone_id}"
  name    = "lastingpowerofattorney.service.gov.uk"
  type    = "A"

  alias {
    evaluate_target_health = false
    name                   = data.aws_lb.new_lpa_production_front.dns_name
    zone_id                = data.aws_lb.new_lpa_production_front.zone_id
    # name    = aws_cloudfront_distribution.maintenance.domain_name
    # zone_id = aws_cloudfront_distribution.maintenance.hosted_zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

output "live_service_url" {
  value = aws_route53_record.lastingpowerofattorney_service_gov_uk
}

resource "aws_route53_record" "maintenance_lastingpowerofattorney_service_gov_uk_a" {
  zone_id = data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.zone_id
  name    = "maintenance.lastingpowerofattorney.service.gov.uk"
  type    = "A"

  alias {
    evaluate_target_health = false
    name                   = aws_cloudfront_distribution.maintenance.domain_name
    zone_id                = aws_cloudfront_distribution.maintenance.hosted_zone_id
    # name    = data.aws_lb.new_lpa_production_front.dns_name
    # zone_id = data.aws_lb.new_lpa_production_front.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

output "maintenance_service_url" {
  value = aws_route53_record.maintenance_lastingpowerofattorney_service_gov_uk_a
}
