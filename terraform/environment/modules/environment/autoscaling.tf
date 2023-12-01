module "front_ecs_autoscaling" {
  source                                        = "./modules/ecs_autoscaling"
  environment                                   = var.environment_name
  aws_ecs_cluster_name                          = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name                          = aws_ecs_service.front.name
  ecs_autoscaling_service_role_arn              = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum                  = var.account.autoscaling.front.minimum
  ecs_task_autoscaling_maximum                  = var.account.autoscaling.front.maximum
  autoscaling_metric_track_request_count_target = 360 # Set to 360 to allow for 25% headroom based on max request count of 480 requests per minute
  request_count_scale_out_cooldown              = 30
  aws_request_count_metric_resource_label       = "${aws_lb.front.arn_suffix}/${aws_lb_target_group.front.arn_suffix}"
  tags                                          = local.front_component_tag
}

module "api_ecs_autoscaling" {
  source                              = "./modules/ecs_autoscaling"
  environment                         = var.environment_name
  aws_ecs_cluster_name                = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name                = aws_ecs_service.api.name
  ecs_autoscaling_service_role_arn    = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum        = var.account.autoscaling.api.minimum
  ecs_task_autoscaling_maximum        = var.account.autoscaling.api.maximum
  autoscaling_metric_track_cpu_target = 45
  tags                                = local.api_component_tag
}

module "pdf_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = var.environment_name
  aws_ecs_cluster_name             = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name             = aws_ecs_service.pdf.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = var.account.autoscaling.pdf.minimum
  ecs_task_autoscaling_maximum     = var.account.autoscaling.pdf.maximum
  tags                             = local.pdf_component_tag
}

module "admin_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = var.environment_name
  aws_ecs_cluster_name             = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name             = aws_ecs_service.admin.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = 1
  ecs_task_autoscaling_maximum     = 2
  tags                             = local.admin_component_tag
}
