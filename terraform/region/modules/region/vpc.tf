#tfsec:ignore:AWS082 this is currently std practice. will look to change later if needed
resource "aws_default_vpc" "default" {
  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "default"
    })
  )
}
