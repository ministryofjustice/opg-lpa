locals {

  # network values taken from terraform/environment/modules/environment/outputs.tf
  network = var.dms_network
  aurora_migration_network_sg_map = var.dms_network == null ? {} : {
    source = local.network.security_group_ids.source
    target = local.network.security_group_ids.target
  }

  common_tags = merge(
    {
      Name      = "aurora-${var.environment_name}-dms"
      Component = "database-migration"
    },
    var.tags
  )

  replication_task_id        = coalesce(try(var.task.id, null), "aurora-${var.environment_name}-dms-task")
  replication_migration_type = try(var.task.migration_type, "full-load-and-cdc")
  replication_table_mappings = coalesce(try(var.task.table_mappings, null), local.default_table_mappings)
  replication_task_settings  = coalesce(try(var.task.settings, null), local.default_task_settings)

  default_table_mappings = jsonencode({
    rules = [
      {
        rule-type      = "selection"
        rule-id        = "1"
        rule-name      = "include-all"
        object-locator = { schema-name = "%", table-name = "%" }
        rule-action    = "include"
      }
    ]
  })

  default_task_settings = jsonencode({
    TargetMetadata = {
      TargetSchema       = ""
      SupportLobs        = true
      FullLobMode        = false
      LimitedSizeLobMode = true
      LobMaxSize         = 32
      BatchApplyEnabled  = false
    }

    FullLoadSettings = {
      TargetTablePrepMode             = "TRUNCATE_BEFORE_LOAD"
      StopTaskCachedChangesApplied    = false
      StopTaskCachedChangesNotApplied = false
      MaxFullLoadSubTasks             = 4
      TransactionConsistencyTimeout   = 600
      CommitRate                      = 10000
    }

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

    ChangeProcessingDdlHandlingPolicy = {
      HandleSourceTableDropped   = true
      HandleSourceTableTruncated = true
      HandleSourceTableAltered   = true
    }

    ErrorBehavior = {
      FailOnNoTablesCaptured               = true
      FailOnTransactionConsistencyBreached = true
    }

    ValidationSettings = {
      EnableValidation = true
      ValidationMode   = "ROW_LEVEL"
      ThreadCount      = 5
      ValidationOnly   = false
    }
  })
}
