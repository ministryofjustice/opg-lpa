data "aws_ecr_repository" "mock_onelogin" {
  name     = "modernising-lpa/mock-onelogin"
  provider = aws.management
}

data "aws_ecr_image" "mock_onelogin" {
  repository_name = data.aws_ecr_repository.mock_onelogin.name
  image_tag       = "latest"
  provider        = aws.management
}

module "mock_onelogin" {
  count       = data.aws_default_tags.current.tags.environment-name != "production" && var.environment.feature_flags.onelogin_enabled ? 1 : 0
  source      = "./modules/mock_onelogin"
  ecs_cluster = aws_ecs_cluster.online-lpa.id
  ecs_execution_role = {
    id  = var.ecs_execution_role.id
    arn = var.ecs_execution_role.arn
  }
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
  redirect_base_url               = "https://${data.aws_default_tags.current.tags.environment-name}.${var.account_name}.front.lpa.opg.service.justice.gov.uk/"
  template_sub                    = "1"
  network = {
    vpc_id              = data.aws_vpc.main.id
    application_subnets = data.aws_subnet.application[*].id
    public_subnets      = data.aws_subnet.lb[*].id
  }
  aws_service_discovery_private_dns_namespace = {
    id   = aws_service_discovery_private_dns_namespace.internal.id
    name = aws_service_discovery_private_dns_namespace.internal.name
  }
  front_app_ecs_service_security_group_id = aws_security_group.front_ecs_service.id
  providers = {
    aws.region = aws
  }
}
