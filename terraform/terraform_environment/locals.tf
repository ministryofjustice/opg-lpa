# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
}

variable "accounts" {
  type = map(
    object({
      account_id                    = string
      is_production                 = string
      front_dns                     = string
      admin_dns                     = string
      front_certificate_domain_name = string
      admin_certificate_domain_name = string
      sirius_api_gateway_endpoint   = string
      sirius_api_gateway_arn        = string
    })
  )
}

# run-time variables
variable "container_version" {
  type    = string
  default = "latest"
}


locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account_id   = var.accounts[local.account_name].account_id

  environment       = terraform.workspace
  dns_namespace_env = local.account_name != "development" ? "" : "${terraform.workspace}."

  multi_az_db             = local.account_name != "development" ? true : false
  backup_retention_period = local.account_name != "development" ? 14 : 0
  asg_desired             = local.account_name != "development" ? 3 : 2
  asg_max                 = local.account_name != "development" ? 6 : 4

  lpa_locks_read_capacity       = local.account_name != "development" ? 2 : 5
  lpa_locks_write_capacity      = local.account_name != "development" ? 2 : 5
  lpa_properties_read_capacity  = local.account_name != "development" ? 20 : 5
  lpa_properties_write_capacity = local.account_name != "development" ? 1 : 5
  lpa_sessions_read_capacity    = local.account_name != "development" ? 20 : 5
  lpa_sessions_write_capacity   = local.account_name != "development" ? 40 : 5

  sirius_api_gateway_endpoint = var.accounts[local.account_name].sirius_api_gateway_endpoint
  sirius_api_gateway_arn      = var.accounts[local.account_name].sirius_api_gateway_arn

  ecs_task_count = 2
  timestamp      = lower(formatdate("YYYYMMDDhhmmss", timestamp()))

  opg_project = "lpa"
  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = var.accounts[local.account_name].is_production
  }

  optional_tags = {
    environment-name       = terraform.workspace
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags, {
    "Name" = "${local.environment}-online-lpa-tool"
  }, )

}
# tags = local.default_tags
