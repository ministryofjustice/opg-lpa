locals {

  opg_project                 = "lpa"
  account_name                = lookup(var.account_mapping, terraform.workspace, "development")
  account_name_short          = local.account.account_name_short
  account                     = var.accounts[local.account_name]
  environment                 = terraform.workspace
  cert_prefix_public_facing   = local.environment == "production" ? "www." : "*."
  cert_prefix_internal        = local.account_name == "production" ? "" : "*."
  dns_namespace_env           = local.environment == "production" ? "" : "${local.environment}."
  dns_namespace_env_public    = local.environment == "production" ? "www." : "${local.environment}."
  dns_namespace_dev_prefix    = local.account_name == "development" ? "development." : ""
  track_from_date             = "2019-04-01"
  front_dns                   = "front.lpa"
  admin_dns                   = "admin.lpa"
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = local.account.is_production
  }

  optional_tags = {
    environment-name       = local.environment
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-lpa/tree/master/docs/runbooks"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags, {
    "Name" = "${local.environment}-online-lpa-tool"
  })

  performance_platform_component_tag = {
    component = "performance_platform"
  }

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

  dynamodb_component_tag = {
    component = "dynamodb"
  }

  db_component_tag = {
    component = "db"
  }

  pdf_component_tag = {
    component = "pdf"
  }

  seeding_component_tag = {
    component = "seeding"
  }

  feedbackdb_component_tag = {
    component = "feedbackdb"
  }
}
