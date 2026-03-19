locals {

  # network values taken from terraform/environment/modules/environment/outputs.t
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
      TargetSchema = ""
      SupportLobs  = false
      # FullLobMode              = true
      # LobChunkSize             = 64
      # InlineLobMaxSize         = 32
      # LimitedSizeLobMode       = false
      # LoadMaxFileSize          = 0
      # ParallelLoadThreads      = 8
      # ParallelLoadBufferSize   = 50
      # BatchApplyEnabled        = true
      # TaskRecoveryTableEnabled = true
    }

    FullLoadSettings = {
      TargetTablePrepMode             = "TRUNCATE_BEFORE_LOAD"
      CreatePkAfterFullLoad           = false
      StopTaskCachedChangesApplied    = false
      StopTaskCachedChangesNotApplied = false
      MaxFullLoadSubTasks             = 1
      # ParallelLoadThreads             = 8
      # ParallelLoadBufferSize          = 50
    }

    Logging = {
      EnableLogging = true
      LogComponents = [
        { Id = "SOURCE_UNLOAD", Severity = "LOGGER_SEVERITY_DEFAULT" },
        { Id = "TARGET_LOAD", Severity = "LOGGER_SEVERITY_DEFAULT" },
        { Id = "TASK_MANAGER", Severity = "LOGGER_SEVERITY_DEFAULT" },
        { Id = "TABLES_MANAGER", Severity = "LOGGER_SEVERITY_DEFAULT" },
        { ID = "METADATA_MANAGER", Severity = "LOGGER_SEVERITY_DEFAULT" },
      ]
    }

    # ControlTablesSettings = {
    #   ControlSchema            = "dms_control"
    #   HistoryTimeslotInMinutes = 5
    #   HistoryTableEnabled      = true
    # }

    # ChangeProcessingDdlHandlingPolicy = {
    #   HandleSourceTableDropped   = true
    #   HandleSourceTableTruncated = true
    #   HandleSourceTableAltered   = true
    # }

    # ChangeProcessingTuning = {
    #   BatchApplyPreserveTransaction = true
    #   BatchApplyTimeoutMin          = 1
    #   BatchApplyMemoryLimit         = 500
    #   BatchSplitSize                = 0
    #   MinTransactionSize            = 1000
    #   CommitTimeout                 = 1
    # }
  })
}
