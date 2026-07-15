data "aws_region" "current" {}

data "aws_caller_identity" "current" {}

data "aws_default_tags" "current" {}

data "aws_secretsmanager_secret" "elasticache_auth_token" {
  name = "${var.account_name}/elasticache_auth_token"
}

data "aws_secretsmanager_secret_version" "elasticache_auth_token" {
  secret_id = data.aws_secretsmanager_secret.elasticache_auth_token.id
}
