resource "aws_security_group" "aurora_migration_replication" {
  name                   = "aurora-${var.environment_name}-dms-replication"
  description            = "Aurora DMS replication instance access for ${var.environment_name}"
  vpc_id                 = var.network.vpc_id
  revoke_rules_on_delete = true
  tags                   = local.common_tags

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_vpc_security_group_egress_rule" "aurora_migration_db_egress" {
  for_each = !var.network.allow_all_egress ? toset(compact([
    try(var.network.source_security_group_id, null),
    try(var.network.target_security_group_id, null)
  ])) : toset([])

  security_group_id            = aws_security_group.aurora_migration_replication.id
  referenced_security_group_id = each.value
  ip_protocol                  = "tcp"
  from_port                    = 5432
  to_port                      = 5432
  description                  = "Allow Aurora DMS outbound to database security group"
}

resource "aws_vpc_security_group_ingress_rule" "database_from_aurora_migration_ingress" {
  for_each = toset(compact([
    try(var.network.source_security_group_id, null),
    try(var.network.target_security_group_id, null)
  ]))

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
  subnet_ids                           = var.network.subnet_ids

  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Aurora DMS Replication Subnet Group"
    }
  )

  depends_on = [aws_iam_role.dms_vpc_management]
}
