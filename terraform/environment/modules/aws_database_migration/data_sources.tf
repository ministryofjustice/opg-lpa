
data "aws_kms_key" "replication" {
  key_id = "alias/opg-lpa-${var.account_name}-rds-encryption-key"
}

data "aws_rds_cluster" "source" {
  cluster_identifier = var.source_config.cluster_identifier
}

data "aws_rds_cluster" "target" {
  cluster_identifier = var.target_config.cluster_identifier
}

data "aws_secretsmanager_secret_version" "source_db_username" {
  secret_id = var.source_config.username_secret_name
}

data "aws_secretsmanager_secret_version" "source_db_password" {
  secret_id = var.source_config.password_secret_name
}

data "aws_secretsmanager_secret_version" "target_db_username" {
  secret_id = var.target_config.username_secret_name
}

data "aws_secretsmanager_secret_version" "target_db_password" {
  secret_id = var.target_config.password_secret_name
}
