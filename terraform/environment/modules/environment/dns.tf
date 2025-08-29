locals {
  dns_namespace_internal_original = (
    "${var.environment_name}-internal"
  )
  dns_namespace_internal_canonical = (
    var.account_name == "development" ?
    "${var.environment_name}.${var.account_name}.opg.lpa.api.ecs.internal" :
    "${var.account_name}.opg.lpa.api.ecs.internal"
  )

}

resource "aws_service_discovery_private_dns_namespace" "internal" {
  name = local.dns_namespace_internal_original
  vpc  = var.account_name == "development" ? data.aws_vpc.main.id : data.aws_vpc.default.id
}

resource "aws_service_discovery_private_dns_namespace" "internal_canonical" {
  name = local.dns_namespace_internal_canonical
  vpc  = var.account_name == "development" ? data.aws_vpc.main.id : data.aws_vpc.default.id
}
