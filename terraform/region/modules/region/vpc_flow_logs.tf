resource "aws_flow_log" "vpc_flow_logs" {
  iam_role_arn    = data.aws_iam_role.vpc_flow_logs.arn
  log_destination = aws_cloudwatch_log_group.vpc_flow_logs_region.arn
  traffic_type    = "ALL"
  vpc_id          = aws_default_vpc.default.id
}

#tfsec:ignore:AWS089 cost to encrypt is expensive. this is legacy so keep for now.
resource "aws_cloudwatch_log_group" "vpc_flow_logs" {
  name = "vpc_flow_logs"
  tags = local.shared_component_tag
}

#tfsec:ignore:AWS089 cost to encrypt is expensive. region support needed
resource "aws_cloudwatch_log_group" "vpc_flow_logs_region" {
  name = "vpc_flow_logs_${local.account_name}_${local.region_name}"
  tags = local.shared_component_tag
}


# this is held at the account level, so we reference it.
data "aws_iam_role" "vpc_flow_logs" {
  name = "vpc_flow_logs"
}
