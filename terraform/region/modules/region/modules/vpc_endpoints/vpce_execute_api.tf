resource "aws_vpc_endpoint" "execute_api" {
  provider            = aws.region
  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${data.aws_region.current.region}.execute-api"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "execute-api-private" }
}

resource "aws_vpc_endpoint_policy" "execute_api" {
  provider        = aws.region
  vpc_endpoint_id = aws_vpc_endpoint.execute_api.id
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
          "execute-api:*"
        ],
        Resource = [
          "arn:aws:execute-api:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:*",
          "arn:aws:execute-api:${data.aws_region.current.region}:${var.data_lpa_api_account_id}:*"
        ]
      }
    ]
  })
}
