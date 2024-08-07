#tfsec:ignore:aws-dynamodb-enable-recovery #tfsec:ignore:aws-dynamodb-table-customer-key #tfsec:ignore:aws-dynamodb-enable-at-rest-encryption short lived dynamo table doesn't hold customer data
resource "aws_dynamodb_table" "lpa-locks" {
  name         = "lpa-locks-${var.environment_name}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"

  attribute {
    name = "id"
    type = "S"
  }

  tags = local.dynamodb_component_tag
}

#tfsec:ignore:aws-dynamodb-enable-recovery #tfsec:ignore:aws-dynamodb-table-customer-key #tfsec:ignore:aws-dynamodb-enable-at-rest-encryption short lived dynamo table doesn't hold customer data
resource "aws_dynamodb_table" "lpa-properties" {
  name         = "lpa-properties-${var.environment_name}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"

  attribute {
    name = "id"
    type = "S"
  }

  tags = local.dynamodb_component_tag
}

#tfsec:ignore:aws-dynamodb-enable-recovery #tfsec:ignore:aws-dynamodb-table-customer-key #tfsec:ignore:aws-dynamodb-enable-at-rest-encryption To be removed soon as session table deprecated
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

  tags = local.dynamodb_component_tag
}
