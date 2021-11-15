resource "aws_flow_log" "vpc_flow_logs" {
  iam_role_arn    = aws_iam_role.vpc_flow_logs.arn
  log_destination = aws_cloudwatch_log_group.vpc_flow_logs.arn
  traffic_type    = "ALL"
  vpc_id          = aws_default_vpc.default.id
  tags            = local.default_tags
}

#tfsec:ignore:AWS089 cost to encrypt is expensive.
resource "aws_cloudwatch_log_group" "vpc_flow_logs" {
  name = "vpc_flow_logs"
  tags = merge(local.default_tags, local.shared_component_tag)
}
