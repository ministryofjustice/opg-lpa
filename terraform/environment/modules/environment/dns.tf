data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

data "aws_route53_zone" "live_service_lasting_power_of_attorney" {
  provider = aws.management
  name     = "lastingpowerofattorney.service.gov.uk"
}

locals {
  dns_namespace_internal = (
    var.account_name == "development" ?
    "${var.environment_name}.${var.account_name}.opg.lpa.api.ecs.internal" :
    "${var.environment_name}-internal"
  )
}

resource "aws_service_discovery_private_dns_namespace" "internal" {
  name = local.dns_namespace_internal
  vpc  = data.aws_vpc.default.id
}
