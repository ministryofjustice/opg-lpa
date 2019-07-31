data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = "aws.management"
  name     = "opg.service.justice.gov.uk"
}

//------------------------
// Front Certificates

resource "aws_route53_record" "certificate_validation_front" {
  provider = "aws.management"
  name     = aws_acm_certificate.certificate_front.domain_validation_options.0.resource_record_name
  type     = aws_acm_certificate.certificate_front.domain_validation_options.0.resource_record_type
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  records  = [aws_acm_certificate.certificate_front.domain_validation_options.0.resource_record_value]
  ttl      = 60
}

resource "aws_acm_certificate_validation" "certificate_front" {
  certificate_arn         = aws_acm_certificate.certificate_front.arn
  validation_record_fqdns = [aws_route53_record.certificate_validation_front.fqdn]
}

resource "aws_acm_certificate" "certificate_front" {
  domain_name       = var.accounts[local.account_name].front_certificate_domain_name
  validation_method = "DNS"
}

//------------------------
// Admin Certificates

resource "aws_route53_record" "certificate_validation_admin" {
  provider = "aws.management"
  name     = aws_acm_certificate.certificate_admin.domain_validation_options.0.resource_record_name
  type     = aws_acm_certificate.certificate_admin.domain_validation_options.0.resource_record_type
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  records  = [aws_acm_certificate.certificate_admin.domain_validation_options.0.resource_record_value]
  ttl      = 60
}

resource "aws_acm_certificate_validation" "certificate_admin" {
  certificate_arn         = aws_acm_certificate.certificate_admin.arn
  validation_record_fqdns = [aws_route53_record.certificate_validation_admin.fqdn]
}

resource "aws_acm_certificate" "certificate_admin" {
  domain_name       = var.accounts[local.account_name].admin_certificate_domain_name
  validation_method = "DNS"
}
