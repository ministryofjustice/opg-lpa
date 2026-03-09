resource "aws_dms_endpoint" "source" {
  endpoint_id                 = "aurora-${var.environment_name}-dms-source"
  endpoint_type               = "source"
  engine_name                 = var.source_config.engine_name
  server_name                 = data.aws_rds_cluster.source.endpoint
  port                        = data.aws_rds_cluster.source.port
  database_name               = data.aws_rds_cluster.source.database_name
  username                    = data.aws_secretsmanager_secret_version.source_db_username.secret_string
  password                    = data.aws_secretsmanager_secret_version.source_db_password.secret_string
  ssl_mode                    = var.source_config.ssl_mode
  certificate_arn             = try(var.source_config.certificate_arn, null)
  extra_connection_attributes = try(var.source_config.extra_connection_attributes, null)
  tags                        = local.common_tags
}

resource "aws_dms_endpoint" "target" {
  endpoint_id                 = "aurora-${var.environment_name}-dms-target"
  endpoint_type               = "target"
  engine_name                 = var.target_config.engine_name
  server_name                 = data.aws_rds_cluster.target.endpoint
  port                        = data.aws_rds_cluster.target.port
  database_name               = data.aws_rds_cluster.target.database_name
  username                    = data.aws_secretsmanager_secret_version.target_db_username.secret_string
  password                    = data.aws_secretsmanager_secret_version.target_db_password.secret_string
  ssl_mode                    = var.target_config.ssl_mode
  certificate_arn             = try(var.target_config.certificate_arn, null)
  extra_connection_attributes = try(var.target_config.extra_connection_attributes, null)
  tags                        = local.common_tags

}
