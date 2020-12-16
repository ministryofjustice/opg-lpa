locals {
  db = { #will be removed once we use aurora in pre and production.
    endpoint = local.account.aurora_enabled ? module.api_aurora[0].endpoint : aws_db_instance.api[0].address
    port     = local.account.aurora_enabled ? module.api_aurora[0].port : aws_db_instance.api[0].port
    name     = local.account.aurora_enabled ? module.api_aurora[0].name : aws_db_instance.api[0].name
    username = local.account.aurora_enabled ? module.api_aurora[0].master_username : aws_db_instance.api[0].username
  }

  opg_project               = "lpa"
  account_name              = lookup(var.account_mapping, terraform.workspace, "development")
  account                   = var.accounts[local.account_name]
  environment               = terraform.workspace
  cert_prefix_public_facing = local.environment == "production" ? "www." : "${local.environment}."
  cert_prefix_internal      = local.account_name == "production" ? "" : "*."
  dns_namespace_env         = local.environment == "production" ? "" : "${local.environment}."
  track_from_date           = "2019-04-01"
  front_dns                 = "front.lpa"
  admin_dns                 = "admin.lpa"

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = local.account.is_production
  }

  optional_tags = {
    environment-name       = local.environment
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags, {
    "Name" = "${local.environment}-online-lpa-tool"
  }, )

}
