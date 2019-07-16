resource "aws_cloudwatch_log_group" "online-lpa-tool" {
  name = "online-lpa-tool"

  tags = merge(
    local.tags,
    {
      "Name" = "online-lpa-tool"
    },
  )
}
