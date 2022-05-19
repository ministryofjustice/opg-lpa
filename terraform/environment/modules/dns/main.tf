
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

