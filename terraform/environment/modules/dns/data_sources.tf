data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

data "aws_route53_zone" "live_service_lasting_power_of_attorney" {
  provider = aws.management
  name     = "lastingpowerofattorney.service.gov.uk"
}

# data "aws_kms_alias" "sns_kms_key_alias_global" {
#   key_id   = "opg-lpa-${var.account_name}-sns-encryption-key"
#   provider = us_east_1
# }
