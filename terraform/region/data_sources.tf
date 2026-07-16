data "aws_caller_identity" "current" {}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}

data "aws_region" "current" {}

data "aws_region" "eu_west_2" {
  provider = aws.eu-west-2
}

# elasticache auth token
data "aws_secretsmanager_secret" "elasticache_auth_token_eu_west_1" {
  name     = "${local.account_name}/elasticache_auth_token"
  provider = aws.eu-west-1
}

# eu-west-2 (if DR is enabled)
data "aws_secretsmanager_secret" "elasticache_auth_token_eu_west_2" {
  name     = "${local.account_name}/elasticache_auth_token"
  provider = aws.eu-west-2
}
