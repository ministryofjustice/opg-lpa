data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

data "aws_route53_zone" "live_service_lasting_power_of_attorney" {
  provider = aws.management
  name     = "lastingpowerofattorney.service.gov.uk"
}
