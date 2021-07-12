#tfsec:ignore:AWS086 #tfsec:ignore:AWS092 short lived dynamo table doesn't hold customer data
resource "aws_dynamodb_table" "lpa-locks" {
  name         = "lpa-locks-${local.environment}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"

  attribute {
    name = "id"
    type = "S"
  }

  tags = merge(local.default_tags, local.dynamodb_component_tag)
}

#tfsec:ignore:AWS086 #tfsec:ignore:AWS092 short lived dynamo table doesn't hold customer data
resource "aws_dynamodb_table" "lpa-properties" {
  name         = "lpa-properties-${local.environment}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"

  attribute {
    name = "id"
    type = "S"
  }

  tags = merge(local.default_tags, local.dynamodb_component_tag)
}

#tfsec:ignore:AWS086 #tfsec:ignore:AWS092 To be removed soon as session table deprecated
resource "aws_dynamodb_table" "lpa-sessions" {
  name         = "lpa-sessions-${local.environment}"
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

  tags = merge(local.default_tags, local.dynamodb_component_tag)
}
