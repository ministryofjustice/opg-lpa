data "aws_route53_zone" "lastingpowerofattorney_service_gov_uk" {
  name = "lastingpowerofattorney.service.gov.uk"
}

resource "aws_route53_record" "certificate_validation_maintenance_cloudfront_dn" {
  name    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.0.resource_record_name
  type    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.0.resource_record_type
  zone_id = data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.id
  records = [aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.0.resource_record_value]
  ttl     = 300
}

resource "aws_route53_record" "certificate_validation_maintenance_cloudfront_san_www" {
  name    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.1.resource_record_name
  type    = aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.1.resource_record_type
  zone_id = data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.id
  records = [aws_acm_certificate.certificate_maintenance_cloudfront.domain_validation_options.1.resource_record_value]
  ttl     = 300
}

# OLD CERT
resource "aws_acm_certificate_validation" "certificate_maintenance_cloudfront" {
  provider        = aws.us_east_1
  certificate_arn = aws_acm_certificate.certificate_maintenance_cloudfront.arn
  validation_record_fqdns = [
    aws_route53_record.certificate_validation_maintenance_cloudfront_dn.fqdn,
    aws_route53_record.certificate_validation_maintenance_cloudfront_san_www.fqdn,
  ]

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


# NEW CERT
resource "aws_route53_record" "cv_maintenance_cloudfront_san_maintenance" {
  name    = aws_acm_certificate.maintenance_cloudfront.domain_validation_options.2.resource_record_name
  type    = aws_acm_certificate.maintenance_cloudfront.domain_validation_options.2.resource_record_type
  zone_id = data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.id
  records = [aws_acm_certificate.maintenance_cloudfront.domain_validation_options.2.resource_record_value]
  ttl     = 300
}

resource "aws_acm_certificate_validation" "maintenance_cloudfront" {
  provider        = aws.us_east_1
  certificate_arn = aws_acm_certificate.maintenance_cloudfront.arn
  validation_record_fqdns = [
    aws_route53_record.certificate_validation_maintenance_cloudfront_dn.fqdn,
    aws_route53_record.certificate_validation_maintenance_cloudfront_san_www.fqdn,
    aws_route53_record.cv_maintenance_cloudfront_san_maintenance.fqdn,
  ]

}

resource "aws_acm_certificate" "maintenance_cloudfront" {
  provider                  = aws.us_east_1
  domain_name               = "lastingpowerofattorney.service.gov.uk"
  subject_alternative_names = ["www.lastingpowerofattorney.service.gov.uk", "maintenance.lastingpowerofattorney.service.gov.uk"]
  validation_method         = "DNS"
  options {
    certificate_transparency_logging_preference = "ENABLED"
  }
  tags = local.default_tags
}
