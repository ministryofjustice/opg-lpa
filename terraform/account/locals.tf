locals {
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  pager_duty_db_service_name  = "${local.pagerduty_account_prefix} Make a Lasting Power of Attorney Database Alerts"
  account_name                = lookup(var.account_mapping, terraform.workspace, "development")
  account                     = var.accounts[local.account_name]
  account_id                  = local.account.account_id
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

  front_component_tag = {
    component = "front"
  }

  db_component_tag = {
    component = "db"
  }

}
