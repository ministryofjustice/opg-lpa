//----------------------------------
// admin ECS Service level config

resource "aws_ecs_service" "admin" {
  name            = "admin"
  cluster         = aws_ecs_cluster.online-lpa.id
  task_definition = aws_ecs_task_definition.admin.arn
  desired_count   = local.account.autoscaling.admin.minimum
  launch_type     = "FARGATE"

  network_configuration {
    security_groups  = [aws_security_group.admin_ecs_service.id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.admin.arn
    container_name   = "web"
    container_port   = 80
  }

  depends_on = [aws_lb.admin, aws_iam_role.admin_task_role, aws_iam_role.execution_role]
  tags       = local.default_tags
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "admin_ecs_service" {
  name_prefix = "${local.environment}-admin-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

// 80 in from the ELB
resource "aws_security_group_rule" "admin_ecs_service_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.admin_ecs_service.id
  source_security_group_id = aws_security_group.admin_loadbalancer.id
}

// Anything out
resource "aws_security_group_rule" "admin_ecs_service_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.admin_ecs_service.id
}

//--------------------------------------
// admin ECS Service Task level config

resource "aws_ecs_task_definition" "admin" {
  family                   = "${local.environment}-admin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.admin_web}, ${local.admin_app}]"
  task_role_arn            = aws_iam_role.admin_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}

//----------------
// Permissions

resource "aws_iam_role" "admin_task_role" {
  name               = "${local.environment}-admin-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.default_tags
}

resource "aws_iam_role_policy" "admin_permissions_role" {
  name   = "${local.environment}-adminApplicationPermissions"
  policy = data.aws_iam_policy_document.admin_permissions_role.json
  role   = aws_iam_role.admin_task_role.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "admin_permissions_role" {
  statement {
    sid = "DynamoDBAccess"

    effect = "Allow"

    actions = [
      "dynamodb:BatchGetItem",
      "dynamodb:BatchWriteItem",
      "dynamodb:DeleteItem",
      "dynamodb:DescribeStream",
      "dynamodb:DescribeTable",
      "dynamodb:GetItem",
      "dynamodb:GetRecords",
      "dynamodb:GetShardIterator",
      "dynamodb:ListStreams",
      "dynamodb:ListTables",
      "dynamodb:PutItem",
      "dynamodb:Query",
      "dynamodb:Scan",
      "dynamodb:UpdateItem",
      "dynamodb:UpdateTable",
    ]

    resources = [
      aws_dynamodb_table.lpa-locks.arn,
      aws_dynamodb_table.lpa-properties.arn,
      aws_dynamodb_table.lpa-sessions.arn,
    ]
  }
}

data "aws_ecr_repository" "lpa_admin_web" {
  provider = aws.management
  name     = "online-lpa/admin_web"
}

data "aws_ecr_repository" "lpa_admin_app" {
  provider = aws.management
  name     = "online-lpa/admin_app"
}

//-----------------------------------------------
// admin ECS Service Task Container level config

locals {
  admin_web = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.lpa_admin_web.repository_url}:${var.container_version}",
    "mountPoints": [],
    "name": "web",
    "portMappings": [
        {
            "containerPort": 80,
            "hostPort": 80,
            "protocol": "tcp"
        }
    ],
    "volumesFrom": [],
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
            "awslogs-group": "${data.aws_cloudwatch_log_group.online-lpa.name}",
            "awslogs-region": "eu-west-1",
            "awslogs-stream-prefix": "${local.environment}.admin-web.online-lpa"
        }
    },
    "environment": [
    {"name": "APP_HOST", "value": "127.0.0.1"},
    {"name": "APP_PORT", "value": "9000"},
    {"name": "TIMEOUT", "value": "60"},
    {"name": "CONTAINER_VERSION", "value": "${var.container_version}"}
    ]
  }
  EOF

  admin_app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.lpa_admin_app.repository_url}:${var.container_version}",
    "mountPoints": [],
    "name": "app",
    "portMappings": [
        {
            "containerPort": 9000,
            "hostPort": 9000,
            "protocol": "tcp"
        }
    ],
    "volumesFrom": [],
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
            "awslogs-group": "${data.aws_cloudwatch_log_group.online-lpa.name}",
            "awslogs-region": "eu-west-1",
            "awslogs-stream-prefix": "${local.environment}.admin-app.online-lpa"
        }
    },
    "secrets": [
      { "name": "OPG_LPA_ADMIN_JWT_SECRET", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_admin_jwt_secret.name}" },
      { "name": "OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients.name}" },
      { "name": "OPG_LPA_COMMON_ADMIN_ACCOUNTS", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_admin_accounts.name}" }
    ],
    "environment": [
      {"name": "OPG_NGINX_SERVER_NAMES", "value": "${local.dns_namespace_env}${local.admin_dns} localhost 127.0.0.1"},
      {"name": "OPG_LPA_STACK_NAME", "value": "${local.environment}"},
      {"name": "OPG_DOCKER_TAG", "value": "${var.container_version}"},
      {"name": "OPG_LPA_STACK_ENVIRONMENT", "value": "${local.account_name}"},
      {"name": "OPG_LPA_COMMON_APPLICATION_LOG_PATH", "value": "/var/log/app/application.log"},
      {"name": "OPG_LPA_COMMON_DYNAMODB_ENDPOINT", "value": ""},
      {"name": "OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE", "value": "${aws_dynamodb_table.lpa-locks.name}"},
      {"name": "OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE", "value": "${aws_dynamodb_table.lpa-sessions.name}"},
      {"name": "OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE", "value": "${aws_dynamodb_table.lpa-properties.name}"},
      {"name": "OPG_PHP_POOL_CHILDREN_MAX", "value": "20"},
      {"name": "OPG_PHP_POOL_REQUESTS_MAX", "value": "500"},
      {"name": "OPG_NGINX_SSL_HSTS_AGE", "value": "31536000"},
      {"name": "OPG_NGINX_SSL_FORCE_REDIRECT", "value": "TRUE"},
      {"name": "OPG_LPA_COMMON_RESQUE_REDIS_HOST", "value": "redisback"},
      {"name": "OPG_LPA_ENDPOINTS_API", "value": "http://${local.api_service_fqdn}"}
      ]
    }
  EOF
}
