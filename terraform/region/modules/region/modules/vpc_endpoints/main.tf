resource "aws_security_group" "vpc_endpoints_private" {
  provider    = aws.region
  name_prefix = "vpc-endpoint-access-private-subnets-${var.vpc_id}"
  description = "VPC Interface Endpoints Security Group"
  vpc_id      = var.vpc_id
  tags        = { Name = "vpc-endpoint-access-private-subnets-${var.vpc_id}" }
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "vpc_endpoints_private_subnet_ingress" {
  provider          = aws.region
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  security_group_id = aws_security_group.vpc_endpoints_private.id
  type              = "ingress"
  cidr_blocks       = var.application_subnets_cidr_blocks
  description       = "Allow Services in Private Subnets of ${data.aws_region.current.region} to connect to VPC Interface Endpoints"
}

resource "aws_security_group_rule" "vpc_endpoints_public_subnet_ingress" {
  provider          = aws.region
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  security_group_id = aws_security_group.vpc_endpoints_private.id
  type              = "ingress"
  cidr_blocks       = var.public_subnets_cidr_blocks
  description       = "Allow Services in Public Subnets of ${data.aws_region.current.region} to connect to VPC Interface Endpoints"
}

locals {
  interface_endpoint = toset(var.interface_endpoint_names)
}

resource "aws_vpc_endpoint" "private" {
  provider = aws.region
  for_each = local.interface_endpoint

  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "${each.value}-private" }
}

resource "aws_vpc_endpoint_policy" "private" {
  provider        = aws.region
  for_each        = local.interface_endpoint
  vpc_endpoint_id = aws_vpc_endpoint.private[each.value].id
  policy = jsonencode({
    "Version" : "2012-10-17",
    "Statement" : [
      {
        "Sid" : "AllowAll",
        "Effect" : "Allow",
        "Principal" : {
          "AWS" : "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
        },
        "Action" : [
          "${startswith(each.value, "ecr") ? "ecr" : each.value}:*"
        ],
        "Resource" : "*"
      }
    ]
  })
}

resource "aws_vpc_endpoint" "s3" {
  provider          = aws.region
  count             = var.s3_endpoint_enabled ? 1 : 0
  vpc_id            = var.vpc_id
  service_name      = "com.amazonaws.${data.aws_region.current.region}.s3"
  route_table_ids   = tolist(var.application_route_tables.ids)
  vpc_endpoint_type = "Gateway"
  policy            = data.aws_iam_policy_document.s3.json
  tags              = { Name = "s3-private" }
}

resource "aws_vpc_endpoint" "dynamodb" {
  provider          = aws.region
  count             = var.dynamodb_endpoint_enabled ? 1 : 0
  vpc_id            = var.vpc_id
  service_name      = "com.amazonaws.${data.aws_region.current.region}.dynamodb"
  route_table_ids   = tolist(var.application_route_tables.ids)
  vpc_endpoint_type = "Gateway"
  policy            = data.aws_iam_policy_document.allow_account_access.json
  tags              = { Name = "dynamodb-private" }
}



data "aws_iam_policy_document" "allow_account_access" {
  provider = aws.region
  statement {
    sid       = "Allow-callers-from-specific-account"
    effect    = "Allow"
    actions   = ["*"]
    resources = ["*"]
    principals {
      type        = "AWS"
      identifiers = ["*"]
    }
    condition {
      test     = "StringEquals"
      variable = "aws:PrincipalAccount"
      values   = [data.aws_caller_identity.current.account_id]
    }
  }
}

data "aws_iam_policy_document" "s3" {
  source_policy_documents = [
    data.aws_iam_policy_document.allow_account_access.json,
    data.aws_iam_policy_document.s3_bucket_access.json,
  ]
}

data "aws_iam_policy_document" "s3_bucket_access" {
  statement {
    sid       = "Access-to-specific-bucket-only"
    effect    = "Allow"
    actions   = ["s3:GetObject"]
    resources = ["arn:aws:s3:::prod-${data.aws_region.current.region}-starport-layer-bucket/*"]
    principals {
      type        = "AWS"
      identifiers = ["*"]
    }
  }
}

# for cloudshell
locals {
  cloudshell_endpoints = toset([
    "ecs-agent",
    "ecs-telemetry",
    "ecs",
    "ssmmessages",
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
  tags                = { Name = "cloudshell-${each.value}-private" }
}

resource "aws_vpc_endpoint" "cloudshell_codecatalyst_global" {
  provider            = aws.region
  vpc_id              = var.vpc_id
  service_name        = "aws.api.global.codecatalyst"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "cloudshell-aws.api.global.codecatalyst-private" }
}
