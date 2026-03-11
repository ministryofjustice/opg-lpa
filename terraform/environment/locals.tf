locals {

  account_name     = lookup(var.account_mapping, terraform.workspace, "development")
  account          = var.accounts[local.account_name]
  environment_name = terraform.workspace

  # this flag enables DR. currently prevented from leaving development, and controlled in tfvars.json.
  dr_enabled = local.account_name == "development" && local.account.dr_enabled

  # this flag enables DMS resources. currently prevented from leaving development, and controlled in tfvars.json.
  database_migration_enabled = local.account.database.database_migration_enabled
  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = local.account.is_production
  }

  optional_tags = {
    environment-name       = local.environment_name
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-lpa/tree/master/docs/runbooks"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags, {
    "Name" = "${local.environment_name}-online-lpa-tool"
  })

  shared_component_tag = {
    component = "shared"
  }

  admin_component_tag = {
    component = "admin"
  }

  front_component_tag = {
    component = "front"
  }

  api_component_tag = {
    component = "api"
  }

  pdf_component_tag = {
    component = "pdf"
  }

  seeding_component_tag = {
    component = "seeding"
  }


  dms_source = local.database_migration_enabled ? {
    cluster_identifier   = "arn:aws:rds:eu-west-1:050256574573:cluster:api2-3196lpal1952create-cluster"
    username_secret_name = "${local.account_name}/api_rds_username"
    password_secret_name = "${local.account_name}/api_rds_password"
    engine_name          = "aurora-postgresql"
    ssl_mode             = "require"
  } : null

  dms_target = local.database_migration_enabled ? {
    cluster_identifier   = "arn:aws:rds:eu-west-1:050256574573:cluster:api2-3196lpal1952create-cluster-clone"
    username_secret_name = "${local.account_name}/api_rds_username"
    password_secret_name = "${local.account_name}/api_rds_password"
    ssl_mode             = "require"
  } : null

  dms_network = local.database_migration_enabled ? {
    vpc_id                   = module.eu-west-1.vpc_id
    subnet_ids               = module.eu-west-1.app_subnet_ids
    source_security_group_id = module.eu-west-1.db_api_security_group_id
    target_security_group_id = module.eu-west-1.db_api_security_group_id
    allow_all_egress         = false
  } : null

  dms_replication_instance = local.database_migration_enabled ? {
    class               = "dms.t3.medium"
    allocated_storage   = 100
    multi_az            = false
    publicly_accessible = false
    apply_immediately   = true
    kms_key_arn         = "alias/opg-lpa-${local.account_name}-rds-encryption-key"
  } : null

  dms_task = local.database_migration_enabled ? {
    id             = "dms-${local.environment_name}-aurora-migration"
    migration_type = "full-load-and-cdc"
    table_mappings = <<EOF
{
  "rules": [
    {
      "rule-type": "selection",
      "rule-id": "1",
      "rule-name": "1",
      "object-locator": {
        "schema-name": "%",
        "table-name": "%"
      },
      "rule-action": "include"
    }
  ]
}
EOF
    settings       = <<EOF
{
  "TargetMetadata": {
    "SupportLobs": true,
    "FullLobMode": false,
    "LobChunkSize": 0,
    "LimitedSizeLobMode": true,
    "LobMaxSize": 32,
    "InlineLobMaxSize": 0,
    "LoadMaxFileSize": 0,
    "ParallelLoadThreads": 0,
    "ParallelLoadBufferSize": 0,
    "BatchApplyEnabled": true
  },
  "FullLoadSettings": {
    "TargetTablePrepMode": "DO_NOTHING",
    "CreatePkAfterFullLoad": false,
    "StopTaskCachedChangesApplied": false,
    "StopTaskCachedChangesNotApplied": false,
    "MaxFullLoadSubTasks": 8,
    "TransactionConsistencyTimeout": 600,
    "CommitRate": 10000
  },
  "Logging": {
    "EnableLogging": true
  }
}
EOF
  } : null

}
