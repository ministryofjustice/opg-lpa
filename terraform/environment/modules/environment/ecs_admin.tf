//----------------------------------
// admin ECS Service level config

resource "aws_ecs_service" "admin" {
  name                               = "admin"
  cluster                            = aws_ecs_cluster.online-lpa.id
  task_definition                    = aws_ecs_task_definition.admin.arn
  desired_count                      = var.account.autoscaling.admin.minimum
  launch_type                        = "FARGATE"
  platform_version                   = "1.4.0"
  propagate_tags                     = "TASK_DEFINITION"
  wait_for_steady_state              = true
  deployment_minimum_healthy_percent = 50
  deployment_maximum_percent         = 200
  network_configuration {
    security_groups  = [aws_security_group.admin_ecs_service.id]
    subnets          = [for subnet in data.aws_subnet.application : subnet.id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.admin.arn
    container_name   = "web"
    container_port   = 8080
  }

  lifecycle {
    create_before_destroy = true
    ignore_changes = [
      desired_count
    ]
  }

  timeouts {
    create = var.environment_name == "production" ? "20m" : "10m"
    update = var.environment_name == "production" ? "20m" : "6m"
  }

  depends_on = [aws_lb.admin]
  tags       = local.admin_component_tag
}

//----------------------------------
// The service's Security Groups

#tfsec:ignore:aws-ec2-add-description-to-security-group - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "admin_ecs_service" {
  name_prefix = "${var.environment_name}-admin-ecs-service"
  vpc_id      = data.aws_vpc.main.id
  tags        = local.admin_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "admin_ecs_service_ingress" {
  type                     = "ingress"
  from_port                = 8080
  to_port                  = 8080
  protocol                 = "tcp"
  security_group_id        = aws_security_group.admin_ecs_service.id
  source_security_group_id = aws_security_group.admin_loadbalancer.id
  description              = "Admin Loadbalancer to Admin ECS - HTTP"
}

resource "aws_security_group_rule" "admin_ecs_service_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:aws-ec2-no-public-egress-sgr - anything out
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.admin_ecs_service.id
  description       = "Admin ECS to Anywhere - All Traffic"
}

//--------------------------------------
// admin ECS Service Task level config

resource "aws_ecs_task_definition" "admin" {
  family                   = "${var.environment_name}-admin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 256
  memory                   = 512
  container_definitions    = "[${local.admin_web}, ${local.admin_app}, ${local.aws_otel_collector}]"
  task_role_arn            = var.ecs_iam_task_roles.admin.arn
  execution_role_arn       = var.ecs_execution_role.arn
  tags                     = local.admin_component_tag
  volume {
    name = "app_tmp"
  }
  volume {
    name = "web_etc"
  }
}

data "aws_ecr_repository" "lpa_admin_web" {
  provider = aws.management
  name     = "online-lpa/admin_web"
}

data "aws_ecr_image" "lpa_admin_web" {
  repository_name = data.aws_ecr_repository.lpa_admin_web.name
  image_tag       = var.container_version
  provider        = aws.management
}

data "aws_ecr_repository" "lpa_admin_app" {
  provider = aws.management
  name     = "online-lpa/admin_app"
}

data "aws_ecr_image" "lpa_admin_app" {
  repository_name = data.aws_ecr_repository.lpa_admin_app.name
  image_tag       = var.container_version
  provider        = aws.management
}


//-----------------------------------------------
// admin ECS Service Task Container level config

locals {
  admin_web = jsonencode(
    {
      cpu       = 1,
      essential = true,
      image     = "${data.aws_ecr_repository.lpa_admin_web.repository_url}@${data.aws_ecr_image.lpa_admin_web.image_digest}",
      mountPoints = [
        {
          containerPath = "/etc",
          sourceVolume  = "web_etc"
          readOnly      = false
        }
      ],
      name = "web",
      portMappings = [
        {
          containerPort = 8080,
          hostPort      = 8080,
          protocol      = "tcp"
        }
      ],
      dependsOn = [
        {
          containerName = "app",
          condition     = "HEALTHY"
        }
      ],
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = var.region_name,
          awslogs-stream-prefix = "${var.environment_name}.admin-web.online-lpa"
        }
      },
      environment = [
        { name = "APP_HOST", value = "127.0.0.1" },
        { name = "APP_PORT", value = "9000" },
        { name = "TIMEOUT", value = "60" },
        { name = "CONTAINER_VERSION", value = var.container_version }
      ]
    }
  )

  admin_app = jsonencode(
    {
      cpu                    = 1,
      essential              = true,
      readonlyRootFilesystem = true,
      image                  = "${data.aws_ecr_repository.lpa_admin_app.repository_url}@${data.aws_ecr_image.lpa_admin_app.image_digest}",
      mountPoints = [
        {
          containerPath = "/tmp",
          sourceVolume  = "app_tmp"
        }
      ],
      name = "app",
      portMappings = [
        {
          containerPort = 9000,
          hostPort      = 9000,
          protocol      = "tcp"
        }
      ],
      healthCheck = {
        command     = ["CMD", "/usr/local/bin/health-check.sh"],
        startPeriod = 90,
        interval    = 10,
        timeout     = 15,
        retries     = 3
      },
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = var.region_name,
          awslogs-stream-prefix = "${var.environment_name}.admin-app.online-lpa"
        }
      },
      secrets = [
        { name = "OPG_LPA_ADMIN_JWT_SECRET", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_admin_jwt_secret.name}" },
        { name = "OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients.name}" },
        { name = "OPG_LPA_COMMON_ADMIN_ACCOUNTS", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_admin_accounts.name}" }
      ],
      environment = [
        { name = "OPG_NGINX_SERVER_NAMES", value = "${local.dns_namespace_env}${local.admin_dns} localhost 127.0.0.1" },
        { name = "OPG_LPA_STACK_NAME", value = var.environment_name },
        { name = "OPG_DOCKER_TAG", value = var.container_version },
        { name = "OPG_LPA_STACK_ENVIRONMENT", value = var.account_name },
        { name = "OPG_LPA_COMMON_APPLICATION_LOG_PATH", value = "/var/log/app/application.log" },
        { name = "OPG_LPA_COMMON_DYNAMODB_ENDPOINT", value = "" },
        { name = "OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE", value = aws_dynamodb_table.lpa-locks.name },
        { name = "OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE", value = aws_dynamodb_table.lpa-sessions.name },
        { name = "OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE", value = aws_dynamodb_table.lpa-properties.name },
        { name = "OPG_PHP_POOL_CHILDREN_MAX", value = "25" },
        { name = "OPG_NGINX_SSL_HSTS_AGE", value = "31536000" },
        { name = "OPG_NGINX_SSL_FORCE_REDIRECT", value = "TRUE" },
        { name = "OPG_LPA_COMMON_RESQUE_REDIS_HOST", value = "redisback" },
        { name = "OPG_LPA_ENDPOINTS_API", value = "http://${local.api_service_fqdn}:8080" }
      ]
    }
  )
}
