//----------------------------------
// Api ECS Service level config

resource "aws_ecs_service" "api" {
  name             = "api"
  cluster          = aws_ecs_cluster.online-lpa.id
  task_definition  = aws_ecs_task_definition.api.arn
  desired_count    = local.account.autoscaling.api.minimum
  launch_type      = "FARGATE"
  platform_version = "1.3.0"

  network_configuration {
    security_groups = [
      aws_security_group.api_ecs_service.id,
      aws_security_group.rds-client.id,
    ]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.api.arn
  }
  tags = local.default_tags
}

//-----------------------------------------------
// Api service discovery

resource "aws_service_discovery_service" "api" {
  name = "api"

  dns_config {
    namespace_id = aws_service_discovery_private_dns_namespace.internal.id

    dns_records {
      ttl  = 10
      type = "A"
    }

    routing_policy = "MULTIVALUE"
  }

  health_check_custom_config {
    failure_threshold = 1
  }
}

//
locals {
  api_service_fqdn = "${aws_service_discovery_service.api.name}.${aws_service_discovery_private_dns_namespace.internal.name}"
}

//----------------------------------
// The Api service's Security Groups

resource "aws_security_group" "api_ecs_service" {
  name_prefix = "${terraform.workspace}-api-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

//----------------------------------
// 80 in from front ECS service

resource "aws_security_group_rule" "api_ecs_service_front_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.front_ecs_service.id
}

//----------------------------------
// 80 in from Actor ECS service

resource "aws_security_group_rule" "api_ecs_service_admin_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.admin_ecs_service.id
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "api_ecs_service_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.api_ecs_service.id
}

//--------------------------------------
// Api ECS Service Task level config

resource "aws_ecs_task_definition" "api" {
  family                   = "${terraform.workspace}-api"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 256
  memory                   = 512
  container_definitions    = "[${local.api_web}, ${local.api_app}]"
  task_role_arn            = aws_iam_role.api_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}


//----------------
// Permissions

resource "aws_iam_role" "api_task_role" {
  name               = "${local.environment}-api-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.default_tags
}

resource "aws_iam_role_policy" "api_permissions_role" {
  name   = "${local.environment}-apiApplicationPermissions"
  policy = data.aws_iam_policy_document.api_permissions_role.json
  role   = aws_iam_role.api_task_role.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "api_permissions_role" {
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

  statement {
    sid = "APIGatewayAccess"
    actions = [
      "execute-api:Invoke",
    ]

    resources = [
      local.account.sirius_api_gateway_arn,
    ]
  }
  statement {
    sid = "s3AccessWrite"
    actions = [
      "s3:PutObject",
      "s3:GetObject",
      "s3:DeleteObject",
      "s3:ListObject",
    ]

    resources = [
      "${data.aws_s3_bucket.lpa_pdf_cache.arn}*",
    ]
  }
  statement {
    sid = "s3AccessRead"
    actions = [
      "s3:ListObject",
      "s3:ListBucket",
      "s3:GetObject",
    ]

    resources = [
      data.aws_s3_bucket.lpa_pdf_cache.arn,
    ]
  }
  statement {
    sid    = "lpaCacheDecrypt"
    effect = "Allow"
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]
    resources = [
      data.aws_s3_bucket.lpa_pdf_cache.arn,
      data.aws_kms_key.lpa_pdf_cache.arn,
    ]
  }
}

data "aws_ecr_repository" "lpa_api_web" {
  provider = aws.management
  name     = "online-lpa/api_web"
}

data "aws_ecr_repository" "lpa_api_app" {
  provider = aws.management
  name     = "online-lpa/api_app"
}

//-----------------------------------------------
// api ECS Service Task Container level config

locals {
  api_web = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "image" : "${data.aws_ecr_repository.lpa_api_web.repository_url}:${var.container_version}",
      "mountPoints" : [],
      "name" : "web",
      "portMappings" : [
        {
          "containerPort" : 80,
          "hostPort" : 80,
          "protocol" : "tcp"
        }
      ],
      "volumesFrom" : [],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : data.aws_cloudwatch_log_group.online-lpa.name,
          "awslogs-region" : "eu-west-1",
          "awslogs-stream-prefix" : "${local.environment}.api-web.online-lpa"
        }
      },
      "environment" : [
        { "name" : "APP_HOST", "value" : "127.0.0.1" },
        { "name" : "APP_PORT", "value" : "9000" },
        { "name" : "TIMEOUT", "value" : "60" },
        { "name" : "CONTAINER_VERSION", "value" : var.container_version }
      ]
    }
  )

  api_app = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "image" : "${data.aws_ecr_repository.lpa_api_app.repository_url}:${var.container_version}",
      "mountPoints" : [],
      "name" : "app",
      "portMappings" : [
        {
          "containerPort" : 9000,
          "hostPort" : 9000,
          "protocol" : "tcp"
        }
      ],
      "volumesFrom" : [],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : data.aws_cloudwatch_log_group.online-lpa.name,
          "awslogs-region" : "eu-west-1",
          "awslogs-stream-prefix" : "${local.environment}.api-app.online-lpa"
        }
      },
      "secrets" : [
        { "name" : "OPG_LPA_API_NOTIFY_API_KEY", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_api_notify_api_key.name}" },
        { "name" : "OPG_LPA_POSTGRES_USERNAME", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_username.name}" },
        { "name" : "OPG_LPA_POSTGRES_PASSWORD", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_password.name}" },
        { "name" : "OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients.name}" },
        { "name" : "OPG_LPA_COMMON_ADMIN_ACCOUNTS", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_admin_accounts.name}" }
      ],
      "environment" : [
        { "name" : "OPG_NGINX_SERVER_NAMES", "value" : "api api-${local.environment}.${local.account_name} localhost 127.0.0.1" },
        { "name" : "OPG_LPA_POSTGRES_HOSTNAME", "value" : local.db.endpoint },
        { "name" : "OPG_LPA_POSTGRES_PORT", "value" : tostring(local.db.port) },
        { "name" : "OPG_LPA_POSTGRES_NAME", "value" : local.db.name },
        { "name" : "OPG_LPA_PROCESSING_STATUS_ENDPOINT", "value" : local.account.sirius_api_gateway_endpoint },
        { "name" : "OPG_LPA_API_TRACK_FROM_DATE", "value" : local.track_from_date },
        { "name" : "OPG_LPA_SEED_DATA", "value" : "true" },
        { "name" : "OPG_LPA_STACK_NAME", "value" : local.environment },
        { "name" : "OPG_DOCKER_TAG", "value" : var.container_version },
        { "name" : "OPG_LPA_STACK_ENVIRONMENT", "value" : local.account_name },
        { "name" : "OPG_LPA_COMMON_APPLICATION_LOG_PATH", "value" : "/var/log/app/application.log" },
        { "name" : "OPG_LPA_AUTH_TOKEN_TTL", "value" : tostring(local.account.auth_token_ttl_secs) },
        { "name" : "OPG_LPA_COMMON_DYNAMODB_ENDPOINT", "value" : "" },
        { "name" : "OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-locks.name },
        { "name" : "OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-sessions.name },
        { "name" : "OPG_LPA_COMMON_API_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-properties.name },
        { "name" : "OPG_PHP_POOL_CHILDREN_MAX", "value" : "20" },
        { "name" : "OPG_PHP_POOL_REQUESTS_MAX", "value" : "500" },
        { "name" : "OPG_NGINX_SSL_HSTS_AGE", "value" : "31536000" },
        { "name" : "OPG_NGINX_SSL_FORCE_REDIRECT", "value" : "TRUE" },
        { "name" : "OPG_LPA_COMMON_RESQUE_REDIS_HOST", "value" : "redisback" },
        { "name" : "OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET", "value" : data.aws_s3_bucket.lpa_pdf_cache.bucket },
        { "name" : "OPG_LPA_COMMON_PDF_QUEUE_URL", "value" : "https://sqs.eu-west-1.amazonaws.com/${local.account.account_id}/lpa-pdf-queue-${local.environment}.fifo" }
      ]
    }
  )
}
