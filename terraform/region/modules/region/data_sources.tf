data "aws_region" "current" {}

data "aws_caller_identity" "current" {}

data "aws_default_tags" "current" {}

data "aws_secretsmanager_secret_version" "elasticache_auth_token" {
  secret_id = var.elasticache_auth_token_secret_id
}

data "aws_kms_alias" "elasticache_encryption_key" {
  name     = "alias/opg-lpa-${var.account_name}-elasticache-encryption-key"
  provider = aws.region
}
