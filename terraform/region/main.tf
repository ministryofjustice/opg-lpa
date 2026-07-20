#manual only right now - take this out later.
module "eu-west-1" {
  source                           = "./modules/region"
  account                          = local.account
  account_name                     = local.account_name
  firewalled_vpc_cidr_range        = local.account.firewalled_vpc_cidr_ranges.eu_west_1
  dynamodb_kms_key_arn             = module.dynamodb_encryption_key.primary_key.arn
  application_logs_kms_key_arn     = module.application_log_group_encryption_key.primary_key.arn
  elasticache_auth_token_secret_id = aws_secretsmanager_secret.elasticache_auth_token.name
  aws_iam_roles = {
    ip_blocker = aws_iam_role.ip_blocker
  }
  providers = {
    aws            = aws
    aws.management = aws.management
    aws.region     = aws.eu-west-1
  }
}

module "eu-west-2" {
  source                           = "./modules/region"
  count                            = local.account.dr_enabled && local.account_name == "development" ? 1 : 0
  account                          = local.account
  account_name                     = local.account_name
  firewalled_vpc_cidr_range        = local.account.firewalled_vpc_cidr_ranges.eu_west_2
  dynamodb_kms_key_arn             = module.dynamodb_encryption_key.replica_keys.eu-west-2.arn
  application_logs_kms_key_arn     = module.application_log_group_encryption_key.replica_keys.eu-west-2.arn
  elasticache_auth_token_secret_id = aws_secretsmanager_secret.elasticache_auth_token.name
  aws_iam_roles = {
    ip_blocker = aws_iam_role.ip_blocker
  }
  providers = {
    aws            = aws.eu-west-2
    aws.management = aws.management_eu_west_2
    aws.region     = aws.eu-west-2
  }
}
