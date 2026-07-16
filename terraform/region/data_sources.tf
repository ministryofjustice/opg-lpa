data "aws_caller_identity" "current" {}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}

data "aws_region" "current" {}

data "aws_region" "eu_west_2" {
  provider = aws.eu-west-2
}

data "aws_secretsmanager_secret" "elasticache_auth_token" {
  name = "${local.account_name}/elasticache_auth_token"
}
data "aws_secretsmanager_secret_version" "elasticache_auth_token" {
  secret_id = data.aws_secretsmanager_secret.elasticache_auth_token.id
}
