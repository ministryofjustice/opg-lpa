module "front_ecs_autoscaling" {
  source                                 = "./modules/ecs_autoscaling"
  environment                            = local.environment
  aws_ecs_cluster_name                   = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name                   = aws_ecs_service.front.name
  ecs_autoscaling_service_role_arn       = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum           = local.account.front_autoscaling_minimum
  ecs_task_autoscaling_maximum           = local.account.front_autoscaling_maximum
  autoscaling_metric_track_cpu_target    = 5
  autoscaling_metric_track_memory_target = 5
}

module "api_ecs_autoscaling" {
  source                                 = "./modules/ecs_autoscaling"
  environment                            = local.environment
  aws_ecs_cluster_name                   = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name                   = aws_ecs_service.api.name
  ecs_autoscaling_service_role_arn       = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum           = local.account.api_autoscaling_minimum
  ecs_task_autoscaling_maximum           = local.account.api_autoscaling_maximum
  autoscaling_metric_track_cpu_target    = 5
  autoscaling_metric_track_memory_target = 5
}

module "pdf_ecs_autoscaling" {
  source                                 = "./modules/ecs_autoscaling"
  environment                            = local.environment
  aws_ecs_cluster_name                   = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name                   = aws_ecs_service.pdf.name
  ecs_autoscaling_service_role_arn       = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum           = local.account.pdf_autoscaling_minimum
  ecs_task_autoscaling_maximum           = local.account.pdf_autoscaling_maximum
  autoscaling_metric_track_cpu_target    = 5
  autoscaling_metric_track_memory_target = 5
}
