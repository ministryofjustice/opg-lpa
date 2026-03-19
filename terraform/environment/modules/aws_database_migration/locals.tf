locals {
  network = var.dms_network
  aurora_migration_network_sg_map = var.dms_network == null ? {} : {
    source = local.network.security_group_ids.source
    target = local.network.security_group_ids.target
  }

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

  task_config = var.task == null ? {} : var.task

  task_table_mappings_from_file = try(file("${path.module}/${local.task_config.table_mappings_file}"), null)
  task_settings_from_file       = try(local.task_config.settings_file == "" ? null : file("${path.module}/${local.task_config.settings_file}"), null)

  replication_task = {
    id             = coalesce(local.task_config.id, "aurora-${var.environment_name}-dms-task")
    migration_type = coalesce(local.task_config.migration_type, "full-load-and-cdc")
    table_mappings = coalesce(
      local.task_config.table_mappings,
      local.task_table_mappings_from_file,
      local.default_table_mappings
    )
    settings = coalesce(
      local.task_config.settings,
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
