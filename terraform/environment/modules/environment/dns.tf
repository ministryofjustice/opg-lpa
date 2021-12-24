data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

data "aws_route53_zone" "live_service_lasting_power_of_attorney" {
  provider = aws.management
  name     = "lastingpowerofattorney.service.gov.uk"
}



resource "aws_service_discovery_private_dns_namespace" "internal" {
  name = "${var.environment_name}-internal"
  vpc  = data.aws_vpc.default.id
}

resource "aws_route53_record" "public_facing_lastingpowerofattorney" {
  provider        = aws.management
  zone_id         = data.aws_route53_zone.live_service_lasting_power_of_attorney.zone_id
  name            = "${local.dns_namespace_env_public}${local.dns_namespace_dev_prefix}${data.aws_route53_zone.live_service_lasting_power_of_attorney.name}"
  type            = "A"
  allow_overwrite = true
  alias {
    evaluate_target_health = false
    name                   = aws_lb.front.dns_name
    zone_id                = aws_lb.front.zone_id
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
    name                   = aws_lb.front.dns_name
    zone_id                = aws_lb.front.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

output "front-domain" {
  value = "https://${aws_route53_record.front.fqdn}/home"
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
    name                   = aws_lb.admin.dns_name
    zone_id                = aws_lb.admin.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

output "admin-domain" {
  value = "https://${aws_route53_record.admin.fqdn}"
}
