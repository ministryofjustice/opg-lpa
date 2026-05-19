resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${var.environment_name}_application_logs"
  retention_in_days = var.account.log_retention_in_days
  kms_key_id        = data.aws_kms_alias.application_log_group_encryption_alias.target_key_arn

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "${var.environment_name}_application_logs"
    },
  )
}
