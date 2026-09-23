data "aws_region" "current" {
  provider = aws.region
}

data "aws_caller_identity" "current" {
  provider = aws.region
}

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
