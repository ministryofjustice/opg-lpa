data "aws_kms_key" "rds" {
  key_id = "alias/aws/rds"
}

locals {
  psql_parameter_group_family_list = [
    "postgres13",
    "postgres14",
    # "postgres15",
    # "postgres16",
    # "postgres17",
  ]
}

data "aws_rds_cluster_parameter_group" "postgresql_aurora_params" {
  for_each = toset(local.psql_parameter_group_family_list)
  name     = lower("${each.value}-cluster-params")
}

module "api_aurora" {
  auto_minor_version_upgrade      = true
  source                          = "./modules/aurora"
  count                           = 1
  aurora_serverless               = var.account.database.aurora_serverless
  account_id                      = data.aws_caller_identity.current.account_id
  availability_zones              = data.aws_availability_zones.aws_zones.names
  apply_immediately               = !var.account.database.deletion_protection
  cluster_identifier              = var.account.database.cluster_identifier
  db_subnet_group_name            = "data"
  deletion_protection             = var.account.database.deletion_protection
  engine_version                  = var.account.database.psql_engine_version
  environment                     = var.environment_name
  aws_rds_cluster_parameter_group = data.aws_rds_cluster_parameter_group.postgresql_aurora_params[var.account.database.psql_parameter_group_family].name
  master_username                 = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  master_password                 = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
  instance_count                  = var.account.database.aurora_instance_count
  instance_class                  = "db.t3.medium"
  kms_key_id                      = data.aws_kms_key.rds.arn
  replication_source_identifier   = ""
  skip_final_snapshot             = !var.account.database.deletion_protection
  vpc_security_group_ids          = [aws_security_group.rds_api.id]
  tags                            = local.db_component_tag
  copy_tags_to_snapshot           = true
  firewalled_networks_enabled     = var.account.firewalled_networks_enabled
}

resource "aws_security_group" "rds-client-old" {
  name_prefix            = "rds-client-${var.environment_name}"
  description            = "rds access for ${var.environment_name}"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group" "rds-api-old" {
  name_prefix            = "rds-api-${var.environment_name}"
  description            = "api rds access"
  vpc_id                 = data.aws_vpc.default.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "rds-api-old" {
  count                    = var.account.database.rds_proxy_routing_enabled ? 0 : 1
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds-client-old.id
  security_group_id        = aws_security_group.rds-api-old.id
  description              = "RDS client to RDS - Postgres"
}

resource "aws_security_group" "rds_client" {
  name_prefix            = "rds-client-${var.environment_name}"
  description            = "rds access for ${var.environment_name}"
  vpc_id                 = data.aws_vpc.main.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group" "rds_api" {
  name_prefix            = "rds-api-${var.environment_name}"
  description            = "api rds access"
  vpc_id                 = data.aws_vpc.main.id
  revoke_rules_on_delete = true
  tags                   = local.db_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "rds_api" {
  count                    = var.account.database.rds_proxy_routing_enabled ? 0 : 1
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds_client.id
  security_group_id        = aws_security_group.rds_api.id
  description              = "RDS client to RDS - Postgres"
}
