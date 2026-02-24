locals {
  is_dev = local.account_name == "development"

  account_name     = lookup(var.account_mapping, terraform.workspace, "development")
  account          = var.accounts[local.account_name]
  environment_name = terraform.workspace

  # this flag enables DR. currently prevented from leaving development, and controlled in tfvars.json.
  dr_enabled = local.account_name == "development" && local.account.dr_enabled

  # flag to enable usage of the aurora default key for non-dev environments, and the custom new key for dev - testing purposes
  # tflint-ignore: terraform_unused_declarations
  kms_key_id = local.is_dev ? data.aws_kms_key.aurora_new_key.arn : data.aws_kms_key.aurora_default_key.arn

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

}
