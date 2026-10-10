
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

resource "aws_route53_record" "spf_public_facing_lastingpowerofattorney" {
  provider = aws.management
  zone_id  = data.aws_route53_zone.live_service_lasting_power_of_attorney.zone_id
  name     = "${local.dns_namespace_env_public}${local.dns_namespace_dev_prefix}${data.aws_route53_zone.live_service_lasting_power_of_attorney.name}"
  type     = "TXT"
  ttl      = "300"

  records = [
    "v=spf1 -all",
  ]

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "spf_public_facing_lastingpowerofattorney_redirect" {
  count    = var.environment_name == "production" ? 1 : 0
  provider = aws.management
  zone_id  = data.aws_route53_zone.live_service_lasting_power_of_attorney.zone_id
  name     = data.aws_route53_zone.live_service_lasting_power_of_attorney.name
  type     = "TXT"
  ttl      = "300"

  records = [
    "v=spf1 -all",
  ]

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "dmarc_public_facing_lastingpowerofattorney" {
  provider = aws.management
  zone_id  = data.aws_route53_zone.live_service_lasting_power_of_attorney.zone_id
  name     = "_dmarc.${local.dns_namespace_env_public}${local.dns_namespace_dev_prefix}${data.aws_route53_zone.live_service_lasting_power_of_attorney.name}"
  type     = "TXT"
  ttl      = "300"

  records = [
    "v=DMARC1; p=reject; sp=reject; fo=1; rua=mailto:dmarc-rua@dmarc.service.gov.uk; ruf=mailto:dmarc-ruf@dmarc.service.gov.uk",
  ]
}

resource "aws_route53_record" "dmarc_public_facing_lastingpowerofattorney_redirect" {
  count    = var.environment_name == "production" ? 1 : 0
  provider = aws.management
  zone_id  = data.aws_route53_zone.live_service_lasting_power_of_attorney.zone_id
  name     = "_dmarc.${data.aws_route53_zone.live_service_lasting_power_of_attorney.name}"
  type     = "TXT"
  ttl      = "300"

  records = [
    "v=DMARC1; p=reject; sp=reject; fo=1; rua=mailto:dmarc-rua@dmarc.service.gov.uk; ruf=mailto:dmarc-ruf@dmarc.service.gov.uk",
  ]
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
