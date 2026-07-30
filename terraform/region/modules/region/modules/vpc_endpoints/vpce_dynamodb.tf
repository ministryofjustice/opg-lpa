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
