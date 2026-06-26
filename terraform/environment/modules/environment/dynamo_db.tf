#tfsec:ignore:aws-dynamodb-enable-recovery
resource "aws_dynamodb_table" "lpa-locks" {
  name         = "lpa-locks-${var.environment_name}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"

  attribute {
    name = "id"
    type = "S"
  }

  server_side_encryption {
    enabled     = true
    kms_key_arn = data.aws_kms_key.dynamodb_encryption_key.arn
  }

  tags = local.dynamodb_component_tag
}

#tfsec:ignore:aws-dynamodb-enable-recovery
resource "aws_dynamodb_table" "lpa-properties" {
  name         = "lpa-properties-${var.environment_name}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"

  attribute {
    name = "id"
    type = "S"
  }

  server_side_encryption {
    enabled     = true
    kms_key_arn = data.aws_kms_key.dynamodb_encryption_key.arn
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
    kms_key_arn = data.aws_kms_key.dynamodb_encryption_key.arn
  }

  tags = local.dynamodb_component_tag
}
