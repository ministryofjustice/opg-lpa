#tfsec:ignore:AWS089 encryption of logs too expensive
resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${var.environment_name}_application_logs"
  retention_in_days = var.account.log_retention_in_days

  tags = merge(
    local.default_opg_tags,
    local.shared_component_tag,
    {
      "Name" = "${var.environment_name}_application_logs"
    },
  )
}
