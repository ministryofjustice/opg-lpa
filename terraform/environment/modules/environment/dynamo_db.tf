locals {
  # Enable deletion protection for DynamoDB tables only in preproduction and production environments. Allow PR envs to delete when workspace cleanup occurs
  dynamodb_deletion_protection_enabled = contains(["preproduction", "production"], var.environment_name)
}

#tfsec:ignore:aws-dynamodb-enable-recovery
resource "aws_dynamodb_table" "lpa-locks" {
  name                        = "lpa-locks-${var.environment_name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "id"
  deletion_protection_enabled = local.dynamodb_deletion_protection_enabled

  attribute {
    name = "id"
    type = "S"
  }

  server_side_encryption {
    enabled     = true
    kms_key_arn = data.aws_kms_alias.dynamodb_encryption_key.target_key_arn
  }

  tags = local.dynamodb_component_tag
}

#tfsec:ignore:aws-dynamodb-enable-recovery
resource "aws_dynamodb_table" "lpa-properties" {
  name                        = "lpa-properties-${var.environment_name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "id"
  deletion_protection_enabled = local.dynamodb_deletion_protection_enabled

  attribute {
    name = "id"
    type = "S"
  }

  server_side_encryption {
    enabled     = true
    kms_key_arn = data.aws_kms_alias.dynamodb_encryption_key.target_key_arn
  }

  tags = local.dynamodb_component_tag
}

#tfsec:ignore:aws-dynamodb-enable-recovery
resource "aws_dynamodb_table" "lpa-sessions" {
  name         = "lpa-sessions-${var.environment_name}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"

  attribute {
    name = "id"
    type = "S"
  }

  ttl {
    attribute_name = "expires"
    enabled        = true
  }

  server_side_encryption {
    enabled     = true
    kms_key_arn = data.aws_kms_alias.dynamodb_encryption_key.target_key_arn
  }

  tags = local.dynamodb_component_tag
}
