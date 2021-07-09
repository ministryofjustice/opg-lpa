#tfsec:ignore:AWS086
resource "aws_dynamodb_table" "workspace_cleanup_table" {
  count        = local.account_name == "development" ? 1 : 0
  name         = "WorkspaceCleanup"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "WorkspaceName"

  attribute {
    name = "WorkspaceName"
    type = "S"
  }

  ttl {
    attribute_name = "ExpiresTTL"
    enabled        = true
  }

  tags = merge(local.default_tags, local.shared_component_tag)

  lifecycle {
    prevent_destroy = true
  }
}
