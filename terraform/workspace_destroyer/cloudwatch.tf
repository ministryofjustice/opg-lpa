resource "aws_cloudwatch_log_group" "workspace_destroyer" {
  name = "workspace_destroyer"

  tags = merge(
    local.default_tags,
    {
      "Name" = "workspace_destroyer"
    },
  )
}
