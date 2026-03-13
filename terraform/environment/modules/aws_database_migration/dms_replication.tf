
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
  kms_key_arn                 = data.aws_kms_key.replication.arn
  replication_subnet_group_id = aws_dms_replication_subnet_group.aurora_migration.id
  vpc_security_group_ids      = [aws_security_group.aurora_migration_replication.id]

  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Aurora DMS Replication Instance"
    }
  )

  depends_on = [
    aws_iam_role_policy_attachment.dms_vpc_role,
    aws_iam_role_policy_attachment.dms_cloudwatch_role,
  ]
}

resource "aws_dms_replication_task" "aurora_migration" {
  replication_task_id       = local.replication_task_id
  migration_type            = local.replication_migration_type
  replication_instance_arn  = aws_dms_replication_instance.aurora_migration.replication_instance_arn
  source_endpoint_arn       = aws_dms_endpoint.source.endpoint_arn
  target_endpoint_arn       = aws_dms_endpoint.target.endpoint_arn
  table_mappings            = local.replication_table_mappings
  replication_task_settings = local.replication_task_settings
  cdc_start_position        = try(var.task.cdc_start_position, null)
  cdc_start_time            = try(var.task.cdc_start_time, null)
  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Aurora DMS Replication Task"
    }
  )
}
