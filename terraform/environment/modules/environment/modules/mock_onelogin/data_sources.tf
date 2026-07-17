data "aws_region" "current" {
  provider = aws.region
}

data "aws_default_tags" "current" {
  provider = aws.region
}

data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}
