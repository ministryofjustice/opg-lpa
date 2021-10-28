locals {
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  pager_duty_db_service_name  = "${local.pagerduty_account_prefix} Make a Lasting Power of Attorney Database Alerts"
  account                     = var.account
  region_name                 = local.account.regions[data.aws_region.current.name].region
  account_name                = var.account_name
  account_name_short          = local.account.account_name_short
  is_primary_region           = var.account.regions[data.aws_region.current.name].is_primary
  pagerduty_account_prefix    = local.account_name == "production" ? "Production" : "Non-Production"

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = var.account.is_production
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
