module "front_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment
  aws_ecs_cluster_name             = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name             = aws_ecs_service.front.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.account.autoscaling.front.minimum
  ecs_task_autoscaling_maximum     = local.account.autoscaling.front.maximum
  tags                             = merge(local.default_tags, local.front_component_tag)
}

module "api_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment
  aws_ecs_cluster_name             = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name             = aws_ecs_service.api.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.account.autoscaling.api.minimum
  ecs_task_autoscaling_maximum     = local.account.autoscaling.api.maximum
  tags                             = merge(local.default_tags, local.api_component_tag)
}

module "pdf_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment
  aws_ecs_cluster_name             = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name             = aws_ecs_service.pdf.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.account.autoscaling.pdf.minimum
  ecs_task_autoscaling_maximum     = local.account.autoscaling.pdf.maximum
  tags                             = merge(local.default_tags, local.pdf_component_tag)
}

module "admin_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment
  aws_ecs_cluster_name             = aws_ecs_cluster.online-lpa.name
  aws_ecs_service_name             = aws_ecs_service.admin.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = 1
  ecs_task_autoscaling_maximum     = 1
  tags                             = merge(local.default_tags, local.pdf_component_tag)
}
