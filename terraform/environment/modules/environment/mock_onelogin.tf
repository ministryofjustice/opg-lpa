data "aws_ecr_repository" "mock_pay" {
  name     = "modernising-lpa/mock-pay"
  provider = aws.management_eu_west_1
}

data "aws_ecr_image" "mock_onelogin" {
  repository_name = data.aws_ecr_repository.mock_onelogin.name
  image_tag       = "latest"
  provider        = aws.management_eu_west_1
}

module "mock_onelogin" {
  count                           = data.aws_default_tags.current.tags.environment-name != "production" && var.mock_onelogin.enabled ? 1 : 0
  source                          = "./modules/mock_onelogin"
  ecs_cluster                     = aws_ecs_cluster.online-lpa.id
  ecs_execution_role              = var.iam_roles.ecs_execution_role
  ecs_task_role                   = var.ecs_iam_task_roles.mock_onelogin.arn
  ecs_service_desired_count       = 1
  ecs_application_log_group_name  = aws_cloudwatch_log_group.application_logs.name
  ecs_capacity_provider           = "FARGATE_SPOT"
  ingress_allow_list_cidr         = module.allowed_ip_list.moj_sites
  repository_url                  = data.aws_ecr_repository.mock_onelogin.repository_url
  image_digest                    = data.aws_ecr_image.mock_onelogin.id
  alb_deletion_protection_enabled = false
  waf_alb_association_enabled     = true
  container_port                  = 8080
  public_access_enabled           = false
  redirect_base_url               = var.app_env_vars.auth_redirect_base_url
  template_sub                    = var.mock_onelogin.template_sub
  network = {
    vpc_id              = data.aws_vpc.main.id
    application_subnets = data.aws_subnet.application[*].id
    public_subnets      = data.aws_subnet.public[*].id
  }
  aws_service_discovery_private_dns_namespace = {
    id   = aws_service_discovery_private_dns_namespace.internal.id
    name = aws_service_discovery_private_dns_namespace.internal.name
  }
  app_ecs_service_security_group_id = module.app.ecs_service_security_group.id
  providers = {
    aws.region = aws.region
  }
}
