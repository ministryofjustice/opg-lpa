data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

data "aws_route53_zone" "live_lastingpowerofattorney_gov_uk" {
  provider = aws.management
  name     = "lastingpowerofattorney.service.gov.uk"
}

data "aws_route53_zone" "lastingpowerofattorney_service_gov_uk" {
  provider = aws.legacy-lpa
  name     = "lastingpowerofattorney.service.gov.uk"
}

//------------------------
// Front Certificates

resource "aws_route53_record" "certificate_validation_front" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_front.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }
  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
}

resource "aws_acm_certificate_validation" "certificate_front" {
  certificate_arn         = aws_acm_certificate.certificate_front.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_front : record.fqdn]
}

resource "aws_acm_certificate" "certificate_front" {
  domain_name       = "${local.dev_wildcard}front.lpa.opg.service.justice.gov.uk"
  validation_method = "DNS"
}

//------------------------
// Admin Certificates

resource "aws_route53_record" "certificate_validation_admin" {
  provider = aws.management

  for_each = {
    for dvo in aws_acm_certificate.certificate_admin.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
}

resource "aws_acm_certificate_validation" "certificate_admin" {
  certificate_arn         = aws_acm_certificate.certificate_admin.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_admin : record.fqdn]
}

resource "aws_acm_certificate" "certificate_admin" {
  domain_name       = "${local.dev_wildcard}admin.lpa.opg.service.justice.gov.uk"
  validation_method = "DNS"
}

//---------------
// new public facing certs on management
//
resource "aws_route53_record" "certificate_validation_public_facing" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_public_facing.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.live_lastingpowerofattorney_gov_uk.zone_id
}

resource "aws_acm_certificate_validation" "certificate_public_facing" {
  certificate_arn         = aws_acm_certificate.certificate_public_facing.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_public_facing : record.fqdn]
}

resource "aws_acm_certificate" "certificate_public_facing" {
  domain_name               = "${local.dev_wildcard}${data.aws_route53_zone.live_lastingpowerofattorney_gov_uk.name}"
  validation_method         = "DNS"
  subject_alternative_names = ["www.${local.dev_wildcard}${data.aws_route53_zone.live_lastingpowerofattorney_gov_uk.name}"]
}

//------------------------
// Live Service Certificate

resource "aws_route53_record" "certificate_validation_live_service" {
  provider = aws.legacy-lpa
  for_each = terraform.workspace == "production" ? {
    for dvo in aws_acm_certificate.certificate_live_service[0].domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  } : {}

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.id
}

resource "aws_acm_certificate_validation" "certificate_live_service" {
  count                   = terraform.workspace == "production" ? 1 : 0
  certificate_arn         = aws_acm_certificate.certificate_live_service[0].arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_live_service : record.fqdn]
}

resource "aws_acm_certificate" "certificate_live_service" {
  count                     = terraform.workspace == "production" ? 1 : 0
  domain_name               = "*.lastingpowerofattorney.service.gov.uk"
  validation_method         = "DNS"
  subject_alternative_names = ["lastingpowerofattorney.service.gov.uk"]
}
