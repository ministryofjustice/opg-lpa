#tfsec:ignore:AWS089 encryption of logs too expensive
resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${local.environment}_application_logs"
  retention_in_days = local.account.log_retention_in_days

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    {
      "Name" = "${local.environment}_application_logs"
    },
  )
}

resource "aws_cloudwatch_log_group" "application_logs_2" {
  name              = "${local.environment}_application_logs_2"
  retention_in_days = local.account.log_retention_in_days

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    {
      "Name" = "${local.environment}_application_logs"
    },
  )
}

