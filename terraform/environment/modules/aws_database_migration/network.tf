
locals {
  network = var.network

  aurora_migration_network_sg_ids = toset(
    compact([
      local.network.source_security_group_id,
      local.network.target_security_group_id
    ])
  )
}

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
  for_each = local.network.allow_all_egress ? toset([]) : local.aurora_migration_network_sg_ids

  security_group_id            = aws_security_group.aurora_migration_replication.id
  referenced_security_group_id = each.value
  ip_protocol                  = "tcp"
  from_port                    = 5432
  to_port                      = 5432
  description                  = "Allow Aurora DMS outbound to database security group"
}

resource "aws_vpc_security_group_ingress_rule" "database_from_aurora_migration_ingress" {
  for_each = local.aurora_migration_network_sg_ids

  security_group_id            = each.value
  referenced_security_group_id = aws_security_group.aurora_migration_replication.id
  ip_protocol                  = "tcp"
  from_port                    = 5432
  to_port                      = 5432
  description                  = "Allow Aurora DMS inbound from replication instance"
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
