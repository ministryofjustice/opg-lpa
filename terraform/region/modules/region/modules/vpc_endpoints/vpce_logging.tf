locals {
  logging_endpoints = toset([
    "logs",
    "rum",
    "monitoring"
  ])
}

resource "aws_vpc_endpoint" "logging" {
  provider = aws.region
  for_each = local.logging_endpoints

  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "${each.value}-private" }
}

resource "aws_vpc_endpoint_policy" "logging" {
  provider        = aws.region
  for_each        = local.logging_endpoints
  vpc_endpoint_id = aws_vpc_endpoint.logging[each.value].id
  policy = jsonencode({
    Version = "2012-10-17",
    Statement = [
      {
        Sid    = "AllowAll",
        Effect = "Allow",
        Principal = {
          AWS = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
        },
        Action = [
          "${each.value}:*"
        ],
        Resource = [
          "arn:aws:${each.value}:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:*",
        ]
      }
    ]
  })
}

resource "aws_vpc_endpoint" "xray" {
  provider            = aws.region
  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.xray"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  policy              = data.aws_iam_policy_document.xray_gateway_endpoint_allow_account_access.json
  tags                = { Name = "xray-private" }
}

data "aws_iam_policy_document" "xray_gateway_endpoint_allow_account_access" {
  provider = aws.region
  statement {
    sid       = "Allow-put-traces-from-specific-account"
    effect    = "Allow"
    actions   = ["xray:PutTraceSegments"]
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
