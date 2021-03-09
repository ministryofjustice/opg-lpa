resource "aws_cloudwatch_log_group" "online-lpa" {
  name              = "online-lpa"
  retention_in_days = local.account.retention_in_days

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    {
      "Name" = "online-lpa"
    },
  )
}
