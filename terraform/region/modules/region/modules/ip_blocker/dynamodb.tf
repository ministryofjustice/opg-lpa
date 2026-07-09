# INFO - Table used for working out which IP addresses should be blocked on our WAF
#trivy:ignore:avd-aws-0024 ignore:avd-aws-0025 - point in time recovery not needed as transient data
resource "aws_dynamodb_table" "blocked_ips_table" {
  name         = "BlockedIPs"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "IP" # Set IP as the primary key

  attribute {
    name = "IP"
    type = "S"
  }

  attribute {
    name = "TimeoutExpiry"
    type = "N"
  }

  attribute {
    name = "BlockCounter"
    type = "N"
  }

  attribute {
    name = "UpdatedAt"
    type = "N"
  }

  global_secondary_index {
    name = "TimeoutExpiryIndex"
    key_schema {
      attribute_name = "TimeoutExpiry"
      key_type       = "HASH"
    }
    projection_type = "ALL"
  }

  global_secondary_index {
    name = "BlockCounterIndex"
    key_schema {
      attribute_name = "BlockCounter"
      key_type       = "HASH"
    }
    projection_type = "ALL"
  }

  global_secondary_index {
    name = "UpdatedAtIndex"
    key_schema {
      attribute_name = "UpdatedAt"
      key_type       = "HASH"
    }
    projection_type = "ALL"
  }

  ttl {
    attribute_name = "ExpiresTTL"
    enabled        = true
  }

  server_side_encryption {
    enabled     = true
    kms_key_arn = var.dynamodb_kms_key_arn
  }

  lifecycle {
    prevent_destroy = false
  }
}
