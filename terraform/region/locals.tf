locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account      = var.accounts[local.account_name]
  account_id   = local.account.account_id

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

  default_opg_tags = merge(local.mandatory_moj_tags, local.optional_tags)

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
