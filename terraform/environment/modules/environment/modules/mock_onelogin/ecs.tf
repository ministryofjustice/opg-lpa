resource "aws_ecs_service" "mock_onelogin" {
  name                  = "mock_onelogin"
  cluster               = var.ecs_cluster
  task_definition       = aws_ecs_task_definition.mock_onelogin.arn
  desired_count         = var.ecs_service_desired_count
  platform_version      = "1.4.0"
  wait_for_steady_state = true
  propagate_tags        = "SERVICE"

  capacity_provider_strategy {
    capacity_provider = var.ecs_capacity_provider
    weight            = 100
  }

  network_configuration {
    security_groups  = [aws_security_group.mock_onelogin_ecs_service.id]
    subnets          = var.network.application_subnets
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.mock_onelogin.arn
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.mock_onelogin.arn
    container_name   = "mock_onelogin"
    container_port   = var.container_port
  }

  lifecycle {
    create_before_destroy = true
  }

  timeouts {
    create = "7m"
    update = "4m"
  }
  provider = aws.region
}

resource "aws_service_discovery_service" "mock_onelogin" {
  name = "mock-onelogin"

  dns_config {
    namespace_id = var.aws_service_discovery_private_dns_namespace.id

    dns_records {
      ttl  = 10
      type = "A"
    }

    routing_policy = "MULTIVALUE"
  }

  provider = aws.region
}

locals {
  mock_onelogin_service_discovery_fqdn = "${aws_service_discovery_service.mock_onelogin.name}.${var.aws_service_discovery_private_dns_namespace.name}"
}

resource "aws_security_group" "mock_onelogin_ecs_service" {
  name_prefix = "${data.aws_default_tags.current.tags.environment-name}-ecs-service"
  description = "mock-onelogin service security group"
  vpc_id      = var.network.vpc_id
  lifecycle {
    create_before_destroy = true
  }
  provider = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_ecs_service_ingress" {
  description              = "Allow Port 80 ingress from the mock-onelogin load balancer"
  type                     = "ingress"
  from_port                = 80
  to_port                  = var.container_port
  protocol                 = "tcp"
  security_group_id        = aws_security_group.mock_onelogin_ecs_service.id
  source_security_group_id = aws_security_group.mock_onelogin_loadbalancer.id
  lifecycle {
    create_before_destroy = true
  }
  provider = aws.region
}


resource "aws_security_group_rule" "mock_one_login_service_app_ingress" {
  description              = "Allow Port 8080 ingress from the app ecs service"
  type                     = "ingress"
  from_port                = var.container_port
  to_port                  = var.container_port
  protocol                 = "tcp"
  security_group_id        = aws_security_group.mock_onelogin_ecs_service.id
  source_security_group_id = var.front_app_ecs_service_security_group_id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_ecs_service_egress" {
  description       = "Allow any egress from service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-ec2-no-public-egress-sgr - open egress for ECR access
  security_group_id = aws_security_group.mock_onelogin_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }
  provider = aws.region
}

resource "aws_ecs_task_definition" "mock_onelogin" {
  family                   = "${data.aws_default_tags.current.tags.environment-name}-mock-onelogin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 256
  memory                   = 512
  container_definitions    = "[${local.mock_onelogin}]"
  task_role_arn            = var.ecs_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn
  provider                 = aws.region
}

resource "aws_iam_role_policy" "mock_onelogin_task_role" {
  name     = "${data.aws_default_tags.current.tags.environment-name}-${data.aws_region.current.region}-mock-onelogin-task-role"
  policy   = data.aws_iam_policy_document.task_role_access_policy.json
  role     = var.ecs_task_role.name
  provider = aws.region
}

data "aws_secretsmanager_secret" "mock_onelogin_client_id" {
  name     = "${var.account_name}/mock-onelogin-client-id"
  provider = aws.region
}

data "aws_iam_policy_document" "task_role_access_policy" {
  policy_id = "${local.policy_region_prefix}task_role_access_policy"
  statement {
    sid    = "${local.policy_region_prefix}EcsSecretAccess"
    effect = "Allow"

    actions = [
      "secretsmanager:GetSecretValue",
      "secretsmanager:DescribeSecret",
    ]

    resources = [
      data.aws_secretsmanager_secret.mock_onelogin_client_id.arn,
    ]
  }
}

locals {
  # TODO: fix this url
  mock_onelogin_url = "https://${data.aws_default_tags.current.tags.environment-name}.${var.account_name}.onelogin.lpa.opg.service.justice.gov.uk"

  mock_onelogin = jsonencode(
    {
      cpu                    = 1,
      essential              = true,
      image                  = "${var.repository_url}@${var.image_digest}",
      mountPoints            = [],
      readonlyRootFilesystem = true
      name                   = "mock_onelogin",
      portMappings = [
        {
          containerPort = var.container_port,
          hostPort      = var.container_port,
          protocol      = "tcp"
        }
      ],
      volumesFrom    = [],
      systemControls = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = var.ecs_application_log_group_name,
          awslogs-region        = data.aws_region.current.region,
          awslogs-stream-prefix = data.aws_default_tags.current.tags.environment-name
          mode                  = "non-blocking"
          max-buffer-size       = "25m"
        }
      },
      secrets = [
        {
          name      = "CLIENT_ID",
          valueFrom = data.aws_secretsmanager_secret.mock_onelogin_client_id.arn
        }
      ],
      environment = [
        {
          name  = "PORT",
          value = tostring(var.container_port)
        },
        {
          name  = "PUBLIC_URL",
          value = local.mock_onelogin_url
        },
        {
          name  = "INTERNAL_URL",
          value = "http://${local.mock_onelogin_service_discovery_fqdn}:${var.container_port}"
        },
        {
          name  = "REDIRECT_URL",
          value = "${var.redirect_base_url}/auth/redirect"
        },
        {
          name  = "TEMPLATE_SUB",
          value = var.template_sub
        },
        {
          name  = "TEMPLATE_RETURN_CODES",
          value = "1"
        },
        {
          name  = "TEMPLATE_SUB_DEFAULT",
          value = "random"
        }
      ]
    }
  )
}
