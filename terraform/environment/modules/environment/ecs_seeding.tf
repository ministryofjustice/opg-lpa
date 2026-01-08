//----------------------------------
// The Api service's Security Groups
#tfsec:ignore:aws-ec2-add-description-to-security-group - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "seeding_ecs_service" {
  name_prefix = "${terraform.workspace}-seeding-ecs-service"
  vpc_id      = local.vpc_id
  tags        = local.seeding_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "seeding_ecs_service_egress" {
  count     = var.environment_name == "production" ? 0 : 1
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:aws-ec2-no-public-egress-sgr - anything out
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.seeding_ecs_service.id
  description       = "Non-production Seeding ECS to Anywhere - All Traffic"
}

//--------------------------------------
// seeding ECS Service Task level config

resource "aws_ecs_task_definition" "seeding" {
  count                    = var.environment_name == "production" ? 0 : 1
  family                   = "${terraform.workspace}-seeding"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 2048
  memory                   = 4096
  container_definitions    = "[${local.seeding_app}]"
  task_role_arn            = var.ecs_iam_task_roles.seeding.arn
  execution_role_arn       = var.ecs_execution_role.arn
  tags                     = local.seeding_component_tag
}

data "aws_ecr_repository" "lpa_seeding_app" {
  provider = aws.management
  name     = "online-lpa/seeding_app"
}

data "aws_ecr_image" "lpa_seeding_app" {
  repository_name = data.aws_ecr_repository.lpa_seeding_app.name
  image_tag       = var.container_version
  provider        = aws.management
}

//-----------------------------------------------
// seeding ECS Service Task Container level config

locals {
  seeding_app = jsonencode(
    {
      cpu         = 1,
      essential   = true,
      image       = "${data.aws_ecr_repository.lpa_seeding_app.repository_url}@${data.aws_ecr_image.lpa_seeding_app.image_digest}",
      mountPoints = [],
      name        = "app",
      portMappings = [
        {
          containerPort = 9000,
          hostPort      = 9000,
          protocol      = "tcp"
        }
      ],
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = var.region_name,
          awslogs-stream-prefix = "${var.environment_name}.seeding.online-lpa"
        }
      },
      secrets = [
        { name = "OPG_LPA_POSTGRES_USERNAME", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_username.name}" },
        { name = "OPG_LPA_POSTGRES_PASSWORD", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_password.name}" }
      ],
      environment = [
        { name = "OPG_LPA_POSTGRES_HOSTNAME", value = var.account.database.rds_proxy_routing_enabled ? module.rds_proxy[0].endpoint : module.api_aurora[0].endpoint },
        { name = "OPG_LPA_POSTGRES_PORT", value = var.account.database.rds_proxy_routing_enabled ? "5432" : tostring(module.api_aurora[0].port) },
        { name = "OPG_LPA_POSTGRES_NAME", value = module.api_aurora[0].database_name },
        { name = "OPG_LPA_STACK_ENVIRONMENT", value = var.account_name }
      ]
  })
}
