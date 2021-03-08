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

  tags = local.default_tags

  lifecycle {
    prevent_destroy = true
  }
}
