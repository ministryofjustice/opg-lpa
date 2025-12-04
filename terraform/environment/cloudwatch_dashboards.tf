locals {
  monitoring_template_vars = {
    region                = data.aws_region.current.region
    account_name          = data.aws_default_tags.current.tags.account-name
    environment_name      = data.aws_default_tags.current.tags.environment-name
    app_loadbalancer_name = module.eu-west-1.front_loadbalancer.name
    nat_gateway_a         = data.aws_nat_gateway.main[0].id
    nat_gateway_b         = data.aws_nat_gateway.main[1].id
    nat_gateway_c         = data.aws_nat_gateway.main[2].id
  }
}

resource "aws_cloudwatch_dashboard" "monitoring" {
  dashboard_name = "${data.aws_default_tags.current.tags.environment-name}-make_monitoring"
  dashboard_body = templatefile(
    "cloudwatch_dashboards/make_monitoring.json.tftpl",
    local.monitoring_template_vars
  )
}
