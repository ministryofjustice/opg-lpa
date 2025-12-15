locals {
  monitoring_template_vars = {
    region                = data.aws_region.current.region
    environment_name      = data.aws_default_tags.current.tags.environment-name
    app_loadbalancer_name = aws_lb.front.name
    cluster_name          = aws_ecs_cluster.online-lpa.name
    service_name          = aws_ecs_service.front.name
  }
}
resource "aws_cloudwatch_dashboard" "monitoring" {
  dashboard_name = "${data.aws_default_tags.current.tags.environment-name}-make_monitoring"
  dashboard_body = templatefile(
    "${path.module}/cloudwatch_dashboards/make_monitoring.json.tftpl",
    local.monitoring_template_vars
  )
}
