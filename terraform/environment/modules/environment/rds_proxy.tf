module "rds_proxy" {
  source                            = "./modules/rds_proxy"
  count                             = var.account.database.rds_proxy_enabled ? 1 : 0
  environment_name                  = var.environment_name
  db_cluster_identifier             = module.api_aurora[0].cluster.id
  api_rds_credentials_secret_arn    = aws_secretsmanager_secret_version.api_rds_credentials[0].arn
  vpc_id                            = local.vpc_id
  vpc_subnet_ids                    = local.data_subnet_ids
  rds_client_security_group_id      = local.rds_client_sg_id
  rds_api_security_group_id         = local.rds_api_sg_id
  secretsmanager_encryption_key_arn = data.aws_kms_alias.multi_region_secrets_encryption_alias.target_key_arn
}
