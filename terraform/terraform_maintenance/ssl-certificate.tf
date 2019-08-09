data "aws_route53_zone" "lastingpowerofattorney_service_gov_uk" {
  # provider = aws.opg-lpa-prod
  name = "lastingpowerofattorney.service.gov.uk"
}

resource "aws_route53_record" "certificate_validation_maintenance_cloudfront_dn" {
  # provider = aws.us_east_1
  name    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.0.resource_record_name
  type    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.0.resource_record_type
  zone_id = data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.id
  records = [aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.0.resource_record_value]
  ttl     = 300
}

resource "aws_route53_record" "certificate_validation_maintenance_cloudfront_san" {
  name    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.1.resource_record_name
  type    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.1.resource_record_type
  zone_id = data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.id
  records = [aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.1.resource_record_value]
  ttl     = 300
}

resource "aws_acm_certificate_validation" "certificate_maintenance_cloudfront" {
  provider                = aws.us_east_1
  certificate_arn         = aws_acm_certificate.certificate_maintenance_cloudfront.arn
  validation_record_fqdns = [aws_route53_record.certificate_validation_maintenance_cloudfront_dn.fqdn, aws_route53_record.certificate_validation_maintenance_cloudfront_san.fqdn]

}

resource "aws_acm_certificate" "certificate_maintenance_cloudfront" {
  provider                  = aws.us_east_1
  domain_name               = "lastingpowerofattorney.service.gov.uk"
  subject_alternative_names = ["www.lastingpowerofattorney.service.gov.uk"]
  validation_method         = "DNS"
  options {
    certificate_transparency_logging_preference = "ENABLED"
  }
  tags = local.default_tags
}
