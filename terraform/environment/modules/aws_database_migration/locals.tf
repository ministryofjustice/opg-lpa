locals {
  network = var.dms_network
  aurora_migration_network_sg_ids = var.dms_network == null ? [] : toset(distinct([
    local.network.security_group_ids.source,
    local.network.security_group_ids.target,
  ]))

  default_table_mappings = file("${path.module}/table-mappings.json")

  default_task_settings_base = jsondecode(file("${path.module}/default-task-settings.json"))
  default_task_settings = jsonencode(merge(
    local.default_task_settings_base,
    {
      Logging = {
        EnableLogging         = true
        CloudWatchLogGroupArn = aws_cloudwatch_log_group.dms_tasks.arn
        LogComponents = [
          { Id = "SOURCE_UNLOAD", Severity = "LOGGER_SEVERITY_DEFAULT" },
          { Id = "TARGET_LOAD", Severity = "LOGGER_SEVERITY_DEFAULT" },
          { Id = "SOURCE_CAPTURE", Severity = "LOGGER_SEVERITY_DEFAULT" },
          { Id = "TARGET_APPLY", Severity = "LOGGER_SEVERITY_DEFAULT" },
          { Id = "TASK_MANAGER", Severity = "LOGGER_SEVERITY_DEFAULT" },
        ]
      }
    }
  ))

  task_table_mappings_from_file = try(file("${path.module}/${var.task.table_mappings_file}"), null)
  task_settings_from_file       = try(var.task.settings_file == "" ? null : file("${path.module}/${var.task.settings_file}"), null)

  replication_task = {
    id             = coalesce(var.task.id, "aurora-${var.environment_name}-dms-task")
    migration_type = coalesce(var.task.migration_type, "full-load-and-cdc")
    table_mappings = coalesce(
      var.task.table_mappings,
      local.task_table_mappings_from_file,
      local.default_table_mappings
    )
    settings = coalesce(
      var.task.settings,
      local.task_settings_from_file,
      local.default_task_settings
    )
  }

  common_tags = merge(
    {
      Name      = "aurora-${var.environment_name}-dms"
      Component = "database-migration"
    },
    var.tags
  )
}
