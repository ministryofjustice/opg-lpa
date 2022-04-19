locals {
  opg_project                 = "lpa"
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  account_name                = lookup(var.account_mapping, terraform.workspace, "development")
  account                     = var.accounts[local.account_name]
  account_id                  = local.account.account_id
  cert_prefix_internal        = local.account_name == "production" ? "" : "*."
  cert_prefix_public_facing   = local.account_name == "production" ? "www." : "*."
  cert_prefix_development     = local.account_name == "development" ? "development." : ""
  pagerduty_account_prefix    = local.account_name == "production" ? "Production" : "Non-Production"
  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = local.account.is_production
  }

  optional_tags = {
    environment-name       = local.account_name
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-lpa/tree/master/docs/runbooks"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)

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

  db_component_tag = {
    component = "db"
  }

  performance_platform_component_tag = {
    component = "performance_platform"
  }

}
