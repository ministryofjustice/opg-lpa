resource "aws_security_group" "aurora_migration_replication" {
  name                   = "aurora-${var.environment_name}-dms-replication"
  description            = "Aurora DMS replication instance access for ${var.environment_name}"
  vpc_id                 = local.network.vpc_id
  revoke_rules_on_delete = true
  tags                   = local.common_tags

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_vpc_security_group_egress_rule" "aurora_migration_db_egress" {
  count = local.dms_source_security_group_id == null ? 0 : 1

  security_group_id            = aws_security_group.aurora_migration_replication.id
  referenced_security_group_id = local.dms_source_security_group_id
  ip_protocol                  = "tcp"
  from_port                    = 5432
  to_port                      = 5432
  description                  = "Allow Aurora DMS outbound to source database security group"
}

resource "aws_vpc_security_group_ingress_rule" "database_from_aurora_migration_ingress" {
  count = local.dms_source_security_group_id == null ? 0 : 1

  security_group_id            = local.dms_source_security_group_id
  referenced_security_group_id = aws_security_group.aurora_migration_replication.id
  ip_protocol                  = "tcp"
  from_port                    = 5432
  to_port                      = 5432
  description                  = "Allow source database inbound from Aurora DMS replication instance"
}

resource "aws_vpc_security_group_egress_rule" "aurora_migration_target_db_egress" {
  count = local.dms_target_security_group_id == null || local.dms_target_security_group_id == local.dms_source_security_group_id ? 0 : 1

  security_group_id            = aws_security_group.aurora_migration_replication.id
  referenced_security_group_id = local.dms_target_security_group_id
  ip_protocol                  = "tcp"
  from_port                    = 5432
  to_port                      = 5432
  description                  = "Allow Aurora DMS outbound to target database security group"
}

resource "aws_vpc_security_group_ingress_rule" "target_database_from_aurora_migration_ingress" {
  count = local.dms_target_security_group_id == null || local.dms_target_security_group_id == local.dms_source_security_group_id ? 0 : 1

  security_group_id            = local.dms_target_security_group_id
  referenced_security_group_id = aws_security_group.aurora_migration_replication.id
  ip_protocol                  = "tcp"
  from_port                    = 5432
  to_port                      = 5432
  description                  = "Allow target database inbound from Aurora DMS replication instance"
}

resource "aws_dms_replication_subnet_group" "aurora_migration" {
  replication_subnet_group_description = "Aurora DMS replication subnet group"
  replication_subnet_group_id          = "aurora-${var.environment_name}-dms-subnet-group"
  subnet_ids                           = local.network.subnet_ids

  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Aurora DMS Replication Subnet Group"
    }
  )

  depends_on = [aws_iam_role.dms_vpc_role]
}
