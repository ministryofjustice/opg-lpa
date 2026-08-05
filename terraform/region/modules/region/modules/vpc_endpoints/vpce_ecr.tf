locals {
  ecr_endpoints = toset([
    "ecr.api",
    "ecr.dkr",
  ])
}

resource "aws_vpc_endpoint" "ecr" {
  provider = aws.region
  for_each = local.ecr_endpoints

  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "${each.value}-private" }
}

resource "aws_vpc_endpoint_policy" "ecr" {
  provider        = aws.region
  for_each        = local.ecr_endpoints
  vpc_endpoint_id = aws_vpc_endpoint.ecr[each.value].id
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
          "${startswith(each.value, "ecr") ? "ecr" : each.value}:*"
        ],
        Resource = [
          "arn:aws:${startswith(each.value, "ecr") ? "ecr" : each.value}:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:*",
          "arn:aws:${startswith(each.value, "ecr") ? "ecr" : each.value}:${data.aws_region.current.region}:${var.management_account_id}:*"
        ]
      },
      {
        Sid    = "AllowGetAuthToken",
        Effect = "Allow",
        Principal = {
          AWS = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
        },
        Action = [
          "ecr:GetAuthorizationToken"
        ],
        Resource = [
          "*",
        ]
      }
    ]
  })
}
