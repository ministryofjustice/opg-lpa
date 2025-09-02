locals {
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  pager_duty_db_service_name  = "${local.pagerduty_account_prefix} Make a Lasting Power of Attorney Database Alerts"
  account                     = var.account
  region_name                 = local.account.regions[data.aws_region.current.region].region
  account_name                = var.account_name
  account_name_short          = local.account.account_name_short
  pagerduty_account_prefix    = local.account_name == "production" ? "Production" : "Non-Production"
  cert_prefix_internal        = local.account_name == "production" ? "" : "*."
  cert_prefix_public_facing   = local.account_name == "production" ? "www." : "*."
  cert_prefix_development     = local.account_name == "development" ? "development." : ""

  shared_component_tag = {
    component = "shared"
  }

  front_component_tag = {
    component = "front"
  }

  pdf_component_tag = {
    component = "pdf"
  }

  db_component_tag = {
    component = "db"
  }

}
