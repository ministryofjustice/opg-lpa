locals {
  db = { #will be removed once we use aurora in pre and production.
    endpoint = local.account.aurora_enabled ? module.api_aurora[0].endpoint : aws_db_instance.api[0].address
    port     = local.account.aurora_enabled ? module.api_aurora[0].port : aws_db_instance.api[0].port
    name     = local.account.aurora_enabled ? module.api_aurora[0].name : aws_db_instance.api[0].name
    username = local.account.aurora_enabled ? module.api_aurora[0].master_username : aws_db_instance.api[0].username
  }

  opg_project                 = "lpa"
  account_name                = lookup(var.account_mapping, terraform.workspace, "development")
  account_name_short          = local.account.account_name_short
  account                     = var.accounts[local.account_name]
  environment                 = terraform.workspace
  region_name                 = "eu-west-1"
  cert_prefix_public_facing   = local.environment == "production" ? "www." : "*."
  cert_prefix_internal        = local.account_name == "production" ? "" : "*."
  dns_namespace_env           = local.environment == "production" ? "" : "${local.environment}."
  dns_namespace_env_public    = local.environment == "production" ? "www." : "${local.environment}."
  dns_namespace_dev_prefix    = local.account_name == "development" ? "development." : ""
  track_from_date             = "2019-04-01"
  front_dns                   = "front.lpa"
  admin_dns                   = "admin.lpa"
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  api_container_definitions   = local.account_name == "development" ? "[${local.api_web}, ${local.api_app}, ${local.mock_gateway}, ${local.mock_sirius}]" : "[${local.api_web}, ${local.api_app}]"
  sirius_api_gateway          = local.account_name == "development" ? "http://gateway:5000/lpa-online-tool/lpas/" : local.account.sirius_api_gateway_endpoint

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
}
