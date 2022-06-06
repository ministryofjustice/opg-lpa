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
