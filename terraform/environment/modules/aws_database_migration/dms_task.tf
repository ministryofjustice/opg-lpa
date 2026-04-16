resource "terraform_data" "migration_type_trigger" {
  triggers_replace = local.replication_task.migration_type
}

resource "aws_dms_replication_task" "aurora_migration" {
  replication_task_id       = local.replication_task.id
  migration_type            = local.replication_task.migration_type
  replication_instance_arn  = aws_dms_replication_instance.aurora_migration.replication_instance_arn
  source_endpoint_arn       = aws_dms_endpoint.source.endpoint_arn
  target_endpoint_arn       = aws_dms_endpoint.target.endpoint_arn
  table_mappings            = local.replication_task.table_mappings
  replication_task_settings = local.replication_task.settings
  cdc_start_position        = try(var.task.cdc_start_position, null)
  cdc_start_time            = try(var.task.cdc_start_time, null)
  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Aurora DMS Replication Task"
    }
  )

  lifecycle {
    replace_triggered_by = [
      terraform_data.migration_type_trigger,
    ]
  }
}
