resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${local.environment}_application_logs"
  retention_in_days = local.account.log_retention_in_days

  tags = merge(
    local.default_tags,
    {
      "Name" = "${local.environment}_application_logs"
    },
  )
}
