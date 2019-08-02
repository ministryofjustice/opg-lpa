resource "aws_dynamodb_table" "lpa-locks" {
  name           = "lpa-locks-${local.environment}"
  billing_mode   = "PROVISIONED"
  read_capacity  = local.lpa_locks_read_capacity
  write_capacity = local.lpa_locks_write_capacity
  hash_key       = "id"

  attribute {
    name = "id"
    type = "S"
  }

  tags = local.default_tags
}

resource "aws_dynamodb_table" "lpa-properties" {
  name           = "lpa-properties-${local.environment}"
  billing_mode   = "PROVISIONED"
  read_capacity  = local.lpa_properties_read_capacity
  write_capacity = local.lpa_properties_write_capacity
  hash_key       = "id"

  attribute {
    name = "id"
    type = "S"
  }

  tags = local.default_tags
}

resource "aws_dynamodb_table" "lpa-sessions" {
  name           = "lpa-sessions-${local.environment}"
  billing_mode   = "PROVISIONED"
  read_capacity  = local.lpa_sessions_read_capacity
  write_capacity = local.lpa_sessions_write_capacity
  hash_key       = "id"

  attribute {
    name = "id"
    type = "S"
  }

  ttl {
    attribute_name = "expires"
    enabled        = true
  }

  tags = local.default_tags
}
