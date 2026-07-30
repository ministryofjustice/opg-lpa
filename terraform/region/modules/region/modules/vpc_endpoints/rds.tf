resource "aws_vpc_endpoint" "rds" {
  provider            = aws.region
  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.rds"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "rds-private" }
}

resource "aws_vpc_endpoint_policy" "rds" {
  provider        = aws.region
  vpc_endpoint_id = aws_vpc_endpoint.rds.id
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
          "rds:*"
        ],
        Resource = [
          "arn:aws:rds::${data.aws_caller_identity.current.account_id}:*",
        ]
      }
    ]
  })
}
