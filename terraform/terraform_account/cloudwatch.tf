resource "aws_cloudwatch_log_group" "online-lpa" {
  name = "online-lpa"

  tags = merge(
    local.default_tags,
    {
      "Name" = "online-lpa"
    },
  )
}
