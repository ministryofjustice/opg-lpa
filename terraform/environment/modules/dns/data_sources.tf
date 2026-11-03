data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

data "aws_route53_zone" "live_service_lasting_power_of_attorney" {
  provider = aws.management
  name     = "lastingpowerofattorney.service.gov.uk"
}

data "aws_kms_alias" "sns_kms_key_alias_global" {
  name     = "alias/opg-lpa-${var.account_name}-sns-encryption-key"
  provider = aws.us_east_1
}

data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}

data "pagerduty_service" "main" {
  name = var.pagerduty_service_name
}
