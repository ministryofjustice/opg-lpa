module "rds_proxy" {
  source                            = "./modules/rds_proxy"
  count                             = 1
  environment_name                  = var.environment_name
  db_cluster_identifier             = module.api_aurora[0].cluster.id
  api_rds_credentials_secret_arn    = aws_secretsmanager_secret_version.api_rds_credentials[0].arn
  vpc_id                            = data.aws_vpc.main.id
  vpc_subnet_ids                    = [for subnet in data.aws_subnet.data : subnet.id]
  rds_client_security_group_id      = aws_security_group.rds_client.id
  rds_api_security_group_id         = aws_security_group.rds_api.id
  secretsmanager_encryption_key_arn = data.aws_kms_alias.multi_region_secrets_encryption_alias.target_key_arn
}
