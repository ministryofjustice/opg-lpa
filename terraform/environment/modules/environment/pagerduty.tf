#temporary fix due to https://github.com/PagerDuty/terraform-provider-pagerduty/issues/523

locals {
  ops_service_id = "PP0UDI9"
  cloudwatch_service_id = (
    var.account_name == "production" ?
    "PVIXHGS" :
    "PS99H42"
  )
}

/*
data "pagerduty_service" "pagerduty" {
  name = var.account.pagerduty_service_name
}

data "pagerduty_service" "pagerduty_ops" {
  name = local.pager_duty_ops_service_name
}
*/

data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}


resource "pagerduty_service_integration" "cloudwatch_integration" {
  name = "${data.pagerduty_vendor.cloudwatch.name} ${var.environment_name} Environment"
  #service = data.pagerduty_service.pagerduty.id
  service = local.cloudwatch_service_id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "pagerduty_service_integration" "cloudwatch_integration_ops" {
  name = "${data.pagerduty_vendor.cloudwatch.name} ${var.environment_name} Environment Ops"
  #service = data.pagerduty_service.pagerduty_ops.id
  service = local.ops_service_id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}
