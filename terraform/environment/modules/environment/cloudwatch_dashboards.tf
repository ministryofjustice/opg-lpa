locals {
  monitoring_template_vars = {
    region                = data.aws_region.current.region
    environment_name      = data.aws_default_tags.current.tags.environment-name
    app_loadbalancer_name = aws_lb.front.name
    cluster_name          = aws_ecs_cluster.online-lpa.name
    service_name          = aws_ecs_service.front.name
    # nat_gateway_a         = data.aws_nat_gateway.main[0].id
    # nat_gateway_b         = data.aws_nat_gateway.main[1].id
    # nat_gateway_c         = data.aws_nat_gateway.main[2].id
  }
}

resource "aws_cloudwatch_dashboard" "monitoring" {
  dashboard_name = "${data.aws_default_tags.current.tags.environment-name}-monitoring-dashboard"
  dashboard_body = templatefile(
    "${path.module}/cloudwatch_dashboards/monitoring_dashboard.json.tftpl",
    local.monitoring_template_vars
  )
}
