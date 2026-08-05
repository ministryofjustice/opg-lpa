locals {
  cloudshell_endpoints = toset([
    "ecs-agent",
    "ecs-telemetry",
    "ecs",
    "ssmmessages",
  ])

  codecatalyst_endpoints = toset([
    "codecatalyst.packages",
    "codecatalyst.git",
  ])
}

resource "aws_vpc_endpoint" "cloudshell" {
  provider            = aws.region
  for_each            = local.cloudshell_endpoints
  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  policy              = data.aws_iam_policy_document.allow_account_access.json
  tags                = { Name = "cloudshell-${each.value}-private" }
}

resource "aws_vpc_endpoint" "codecatalyst" {
  provider            = aws.region
  for_each            = var.codecatalyst_endpoints_enabled ? local.codecatalyst_endpoints : []
  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "cloudshell-${each.value}-private" }
}

resource "aws_vpc_endpoint" "cloudshell_codecatalyst_global" {
  count               = var.codecatalyst_endpoints_enabled ? 1 : 0
  provider            = aws.region
  vpc_id              = var.vpc_id
  service_name        = "aws.api.global.codecatalyst"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "cloudshell-aws.api.global.codecatalyst-private" }
}
