//----------------------------------
// DB Migrations ECS Service level config
# runs before the main api service is started
data "aws_ecs_task_execution" "migrations" {
  cluster          = aws_ecs_cluster.online-lpa.id
  task_definition  = aws_ecs_task_definition.migrations.arn
  desired_count    = 1
  launch_type      = "FARGATE"
  platform_version = "1.4.0"
  propagate_tags   = "TASK_DEFINITION"
  group            = "migrations:${aws_ecs_task_definition.migrations.revision}"
  network_configuration {
    security_groups = [
      aws_security_group.api_ecs_service.id,
      aws_security_group.rds-client.id,
    ]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }
  tags = local.api_component_tag
  depends_on = [
    module.api_aurora[0],
  ]
}

//--------------------------------------
// Api ECS Service Task level config
locals {
  migrations_container_definitions_with_pg_bouncer = "[${local.aws_otel_collector}, ${local.migrations}, ${local.pgbouncer}]"
  migrations_container_definitions                 = "[${local.aws_otel_collector}, ${local.migrations}]"
}
resource "aws_ecs_task_definition" "migrations" {
  family                   = "${terraform.workspace}-migrations"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = var.account.database.rds_proxy_routing_enabled ? local.migrations_container_definitions : local.migrations_container_definitions_with_pg_bouncer
  task_role_arn            = var.ecs_iam_task_roles.api.arn
  execution_role_arn       = var.ecs_execution_role.arn
  tags                     = local.api_component_tag
  volume {
    name = "app_tmp"
  }
  volume {
    name = "web_etc"
  }
}

//-----------------------------------------------
// api ECS Service Task Container level config

locals {
  migrations = jsonencode(
    {
      cpu                    = 1,
      essential              = true,
      readonlyRootFilesystem = true,
      image                  = "${data.aws_ecr_repository.lpa_api_app.repository_url}@${data.aws_ecr_image.lpa_api_app.image_digest}",
      name                   = "migrations",
      mountPoints = [
        {
          containerPath = "/tmp",
          sourceVolume  = "app_tmp"
          readOnly      = false
        }
      ],
      portMappings = [
        {
          containerPort = 9001,
          hostPort      = 9001,
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
      command     = ["/bin/sh", "-c", "/usr/local/bin/db-migrations.sh"]
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = var.region_name,
          awslogs-stream-prefix = "${var.environment_name}.api-app.online-lpa"
        }
      },
      dependsOn = [
        {
          containerName = "pgbouncer",
          condition     = "HEALTHY"
        }
      ],
      secrets = [
        { name = "OPG_LPA_API_NOTIFY_API_KEY", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_api_notify_api_key.name}" },
        { name = "OPG_LPA_POSTGRES_USERNAME", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_username.name}" },
        { name = "OPG_LPA_POSTGRES_PASSWORD", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_password.name}" },
        { name = "OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients.name}" },
        { name = "OPG_LPA_COMMON_ADMIN_ACCOUNTS", valueFrom = "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_admin_accounts.name}" }
      ],
      environment = [
        { name = "OPG_NGINX_SERVER_NAMES", value = "api api-${var.environment_name}.${var.account_name} localhost 127.0.0.1" },
        { name = "OPG_LPA_POSTGRES_HOSTNAME", value = var.account.database.rds_proxy_routing_enabled ? module.rds_proxy[0].endpoint : "127.0.0.1" },
        { name = "OPG_LPA_POSTGRES_PORT", value = var.account.database.rds_proxy_routing_enabled ? "5432" : "6432" },
        { name = "OPG_LPA_POSTGRES_NAME", value = module.api_aurora[0].database_name },
        { name = "OPG_LPA_PROCESSING_STATUS_ENDPOINT", value = var.account.sirius_api_gateway_endpoint },
        { name = "OPG_LPA_API_TRACK_FROM_DATE", value = local.track_from_date },
        { name = "OPG_LPA_STACK_NAME", value = var.environment_name },
        { name = "OPG_DOCKER_TAG", value = var.container_version },
        { name = "OPG_LPA_STACK_ENVIRONMENT", value = var.account_name },
        { name = "OPG_LPA_COMMON_APPLICATION_LOG_PATH", value = "/var/log/app/application.log" },
        { name = "OPG_LPA_AUTH_TOKEN_GENERATION", value = var.account_name == "production" ? "random" : "hash" },
        { name = "OPG_LPA_AUTH_TOKEN_TTL", value = tostring(var.account.auth_token_ttl_secs) },
        { name = "OPG_LPA_COMMON_DYNAMODB_ENDPOINT", value = "" },
        { name = "OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE", value = aws_dynamodb_table.lpa-locks.name },
        { name = "OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE", value = aws_dynamodb_table.lpa-sessions.name },
        { name = "OPG_LPA_COMMON_API_DYNAMODB_TABLE", value = aws_dynamodb_table.lpa-properties.name },
        { name = "OPG_PHP_POOL_CHILDREN_MAX", value = "25" },
        { name = "OPG_NGINX_SSL_HSTS_AGE", value = "31536000" },
        { name = "OPG_NGINX_SSL_FORCE_REDIRECT", value = "TRUE" },
        { name = "OPG_LPA_COMMON_RESQUE_REDIS_HOST", value = "redisback" },
        { name = "OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET", value = data.aws_s3_bucket.lpa_pdf_cache.bucket },
        { name = "OPG_LPA_COMMON_PDF_QUEUE_URL", value = "https://sqs.${var.region_name}.amazonaws.com/${var.account.account_id}/lpa-pdf-queue-${var.environment_name}.fifo" },
        { name = "OPG_LPA_TELEMETRY_HOST", value = "127.0.0.1" },
        { name = "OPG_LPA_TELEMETRY_PORT", value = "2000" }
      ]
    }
  )
}
