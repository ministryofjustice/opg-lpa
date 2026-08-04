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

data "aws_kms_alias" "pdf_cache_s3_encryption_key" {
  name     = "alias/opg-lpa-${var.account_name}-pdf-cache-s3-encryption-key"
  provider = aws.region
}

data "aws_kms_alias" "sns_encryption_key" {
  name     = "alias/opg-lpa-${var.account_name}-sns-encryption-key"
  provider = aws.region
}

data "aws_kms_alias" "redacted_logs_s3_encryption_key" {
  name     = "alias/opg-lpa-${var.account_name}-redacted-logs-s3-encryption-key"
  provider = aws.region
}
