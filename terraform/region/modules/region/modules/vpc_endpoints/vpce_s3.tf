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

data "aws_iam_policy_document" "s3" {
  source_policy_documents = [
    data.aws_iam_policy_document.s3_gateway_endpoint_allow_account_access.json,
    data.aws_iam_policy_document.s3_bucket_access.json,
  ]
}

data "aws_iam_policy_document" "s3_gateway_endpoint_allow_account_access" {
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
