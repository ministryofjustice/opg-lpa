//----------------------------------
// Api ECS Service level config

resource "aws_ecs_service" "api" {
  name                               = "api"
  cluster                            = aws_ecs_cluster.online-lpa.id
  task_definition                    = aws_ecs_task_definition.api.arn
  desired_count                      = var.account.autoscaling.api.minimum
  launch_type                        = "FARGATE"
  platform_version                   = "1.3.0"
  propagate_tags                     = "TASK_DEFINITION"
  wait_for_steady_state              = true
  deployment_minimum_healthy_percent = 50
  deployment_maximum_percent         = 200
  network_configuration {
    security_groups = [
      aws_security_group.api_ecs_service.id,
      aws_security_group.rds-client.id,
    ]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.api_canonical.arn
  }

  lifecycle {
    create_before_destroy = true
    ignore_changes = [
      desired_count
    ]
  }

  tags = local.api_component_tag
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

}

resource "aws_service_discovery_service" "api_canonical" {
  name = "api"

  dns_config {
    namespace_id = aws_service_discovery_private_dns_namespace.internal_canonical.id

    dns_records {
      ttl  = 10
      type = "A"
    }

    routing_policy = "MULTIVALUE"
  }

}
locals {

  api_service_fqdn = (
    "${aws_service_discovery_service.api_canonical.name}.${aws_service_discovery_private_dns_namespace.internal_canonical.name}"
  )
}

//----------------------------------
// The Api service's Security Groups
#tfsec:ignore:aws-ec2-add-description-to-security-group - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "api_ecs_service" {
  name_prefix = "${terraform.workspace}-api-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.api_component_tag
}

resource "aws_security_group_rule" "api_ecs_service_front_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.front_ecs_service.id
  description              = "Frontend ECS to API ECS - HTTP"
}

resource "aws_security_group_rule" "api_ecs_service_admin_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.admin_ecs_service.id
  description              = "Admin ECS to API ECS - HTTP"
}

resource "aws_security_group_rule" "api_ecs_service_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:aws-ec2-no-public-egress-sgr - anything out
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.api_ecs_service.id
  description       = "API ECS to Anywhere - All Traffic"
}

//--------------------------------------
// Api ECS Service Task level config

resource "aws_ecs_task_definition" "api" {
  family                   = "${terraform.workspace}-api"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.api_web}, ${local.api_app}, ${local.app_init_container}, ${local.aws_otel_collector}, ${local.pgbouncer}]"
  task_role_arn            = var.ecs_iam_task_roles.api.arn
  execution_role_arn       = var.ecs_execution_role.arn
  tags                     = local.api_component_tag
  volume {
    name = "app_tmp"
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

data "aws_ecr_repository" "lpa_pgbouncer" {
  provider = aws.management
  name     = "online-lpa/pgbouncer"
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
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : var.region_name,
          "awslogs-stream-prefix" : "${var.environment_name}.api-web.online-lpa"
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

  pgbouncer = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "image" : "${data.aws_ecr_repository.lpa_pgbouncer.repository_url}:${var.container_version}",
      "name" : "pgbouncer",
      "mountPoints" : [],
      "healthCheck" : {
        "command" : ["CMD", "bash", "-c", "echo -n > /dev/tcp/127.0.0.1/6432 || exit 1"]
        "startPeriod" : 90,
        "interval" : 10,
        "timeout" : 15,
        "retries" : 3
      },
      "portMappings" : [
        {
          "containerPort" : 6432,
          "hostPort" : 6432,
          "protocol" : "tcp"
        }
      ],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : var.region_name,
          "awslogs-stream-prefix" : "${var.environment_name}.pgbouncer.online-lpa"
        }
      },
      "secrets" : [
        { "name" : "POSTGRESQL_USERNAME", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_username.name}" },
        { "name" : "POSTGRESQL_PASSWORD", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_password.name}" },
      ],
      "environment" : [
        { "name" : "POSTGRESQL_DATABASE", "value" : module.api_aurora[0].name },
        { "name" : "PGBOUNCER_DATABASE", "value" : module.api_aurora[0].name },
        { "name" : "POSTGRESQL_HOST", "value" : module.api_aurora[0].endpoint },
        { "name" : "PGBOUNCER_SERVER_TLS_SSLMODE", "value" : "verify-full" },
        { "name" : "PGBOUNCER_AUTH_TYPE", "value" : "md5" },
      ],
    }
  )

  api_app = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "readonlyRootFilesystem" : true,
      "image" : "${data.aws_ecr_repository.lpa_api_app.repository_url}:${var.container_version}",
      "name" : "app",
      "mountPoints" : [
        {
          "containerPath" : "/tmp",
          "sourceVolume" : "app_tmp"
        }
      ],
      "portMappings" : [
        {
          "containerPort" : 9000,
          "hostPort" : 9000,
          "protocol" : "tcp"
        }
      ],
      "healthCheck" : {
        "command" : ["CMD", "/usr/local/bin/health-check.sh"],
        "startPeriod" : 90,
        "interval" : 10,
        "timeout" : 15,
        "retries" : 3
      },
      "volumesFrom" : [],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : var.region_name,
          "awslogs-stream-prefix" : "${var.environment_name}.api-app.online-lpa"
        }
      },
      "dependsOn" : [
        {
          "containerName" : "permissions-init",
          "condition" : "SUCCESS"
        },
        {
          "containerName" : "pgbouncer",
          "condition" : "HEALTHY"
        }
      ],
      "secrets" : [
        { "name" : "OPG_LPA_API_NOTIFY_API_KEY", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_api_notify_api_key.name}" },
        { "name" : "OPG_LPA_POSTGRES_USERNAME", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_username.name}" },
        { "name" : "OPG_LPA_POSTGRES_PASSWORD", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_password.name}" },
        { "name" : "OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients.name}" },
        { "name" : "OPG_LPA_COMMON_ADMIN_ACCOUNTS", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_common_admin_accounts.name}" }
      ],
      "environment" : [
        { "name" : "OPG_NGINX_SERVER_NAMES", "value" : "api api-${var.environment_name}.${var.account_name} localhost 127.0.0.1" },
        { "name" : "OPG_LPA_POSTGRES_HOSTNAME", "value" : "127.0.0.1" },
        { "name" : "OPG_LPA_POSTGRES_PORT", "value" : "6432" },
        { "name" : "OPG_LPA_POSTGRES_NAME", "value" : module.api_aurora[0].name },
        { "name" : "OPG_LPA_PROCESSING_STATUS_ENDPOINT", "value" : var.account.sirius_api_gateway_endpoint },
        { "name" : "OPG_LPA_API_TRACK_FROM_DATE", "value" : local.track_from_date },
        { "name" : "OPG_LPA_SEED_DATA", "value" : "true" },
        { "name" : "OPG_LPA_STACK_NAME", "value" : var.environment_name },
        { "name" : "OPG_DOCKER_TAG", "value" : var.container_version },
        { "name" : "OPG_LPA_STACK_ENVIRONMENT", "value" : var.account_name },
        { "name" : "OPG_LPA_COMMON_APPLICATION_LOG_PATH", "value" : "/var/log/app/application.log" },
        { "name" : "OPG_LPA_AUTH_TOKEN_TTL", "value" : tostring(var.account.auth_token_ttl_secs) },
        { "name" : "OPG_LPA_COMMON_DYNAMODB_ENDPOINT", "value" : "" },
        { "name" : "OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-locks.name },
        { "name" : "OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-sessions.name },
        { "name" : "OPG_LPA_COMMON_API_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-properties.name },
        { "name" : "OPG_PHP_POOL_CHILDREN_MAX", "value" : "25" },
        { "name" : "OPG_NGINX_SSL_HSTS_AGE", "value" : "31536000" },
        { "name" : "OPG_NGINX_SSL_FORCE_REDIRECT", "value" : "TRUE" },
        { "name" : "OPG_LPA_COMMON_RESQUE_REDIS_HOST", "value" : "redisback" },
        { "name" : "OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET", "value" : data.aws_s3_bucket.lpa_pdf_cache.bucket },
        { "name" : "OPG_LPA_COMMON_PDF_QUEUE_URL", "value" : "https://sqs.${var.region_name}.amazonaws.com/${var.account.account_id}/lpa-pdf-queue-${var.environment_name}.fifo" },
        { "name" : "OPG_LPA_TELEMETRY_HOST", "value" : "127.0.0.1" },
        { "name" : "OPG_LPA_TELEMETRY_PORT", "value" : "2000" }
      ]
    }
  )
}
