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

resource "aws_vpc_security_group_egress_rule" "aurora_migration_all_tcp_egress" {
  count = var.network.allow_all_egress ? 1 : 0

  security_group_id = aws_security_group.aurora_migration_replication.id
  cidr_ipv4         = "0.0.0.0/0"
  ip_protocol       = "tcp"
  from_port         = 0
  to_port           = 65535
  description       = "Allow Aurora DMS replication instance outbound TCP"
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

resource "aws_dms_replication_instance" "aurora_migration" {
  replication_instance_id     = "aurora-${var.environment_name}-dms-instance"
  replication_instance_class  = var.replication_instance.class
  allocated_storage           = var.replication_instance.allocated_storage
  availability_zone           = try(var.replication_instance.availability_zone, null)
  engine_version              = try(var.replication_instance.engine_version, null)
  multi_az                    = var.replication_instance.multi_az
  publicly_accessible         = var.replication_instance.publicly_accessible
  auto_minor_version_upgrade  = true
  apply_immediately           = var.replication_instance.apply_immediately
  kms_key_arn                 = var.replication_instance.kms_key_arn
  replication_subnet_group_id = aws_dms_replication_subnet_group.aurora_migration.id
  vpc_security_group_ids      = [aws_security_group.aurora_migration_replication.id]

  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Aurora DMS Replication Instance"
    }
  )

  depends_on = [
    aws_iam_role_policy_attachment.dms_vpc_management,
    aws_iam_role_policy_attachment.dms_cloudwatch_logs,
    aws_iam_role_policy_attachment.dms_kms_access
  ]
}

resource "aws_dms_replication_task" "aurora_migration" {
  replication_task_id       = var.task.id
  migration_type            = var.task.migration_type
  replication_instance_arn  = aws_dms_replication_instance.aurora_migration.replication_instance_arn
  source_endpoint_arn       = aws_dms_endpoint.source.endpoint_arn
  target_endpoint_arn       = aws_dms_endpoint.target.endpoint_arn
  table_mappings            = var.task.table_mappings
  replication_task_settings = var.task.settings
  cdc_start_position        = try(var.task.cdc_start_position, null)
  cdc_start_time            = try(var.task.cdc_start_time, null)
  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Aurora DMS Replication Task"
    }
  )
}
