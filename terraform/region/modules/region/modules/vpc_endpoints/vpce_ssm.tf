# ssmmessages is in vpce_cloudshell.tf
locals {
  systems_manager_endpoints = toset([
    "ssm", # used for parameter store
    # "ec2messages",   # required for systems manager old ssm agent. not used
    "ssm-contacts",  # required for compliance with security hub fsbp control EC2.58
    "ssm-incidents", # required for compliance with security hub fsbp control EC2.60
  ])
}

resource "aws_vpc_endpoint" "systems_manager" {
  provider = aws.region
  for_each = local.systems_manager_endpoints

  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "${each.value}-private" }
}

resource "aws_vpc_endpoint_policy" "systems_manager" {
  provider        = aws.region
  vpc_endpoint_id = aws_vpc_endpoint.systems_manager[each.value].id
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
          "ssm:*"
        ],
        Resource = [
          "arn:aws:${each.value}:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:*",
        ]
      }
    ]
  })
}
