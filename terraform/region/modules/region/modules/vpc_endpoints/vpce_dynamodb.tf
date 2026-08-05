resource "aws_vpc_endpoint" "dynamodb" {
  provider          = aws.region
  count             = var.dynamodb_endpoint_enabled ? 1 : 0
  vpc_id            = var.vpc_id
  service_name      = "com.amazonaws.${data.aws_region.current.region}.dynamodb"
  route_table_ids   = tolist(var.application_route_tables.ids)
  vpc_endpoint_type = "Gateway"
  policy            = data.aws_iam_policy_document.dynamodb_gateway_endpoint_allow_account_access.json
  tags              = { Name = "dynamodb-private" }
}

data "aws_iam_policy_document" "dynamodb_gateway_endpoint_allow_account_access" {
  provider = aws.region
  statement {
    sid     = "Allow-callers-from-specific-account"
    effect  = "Allow"
    actions = ["*"]
    resources = [
      "arn:aws:dynamodb:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:table/*"
    ]
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
