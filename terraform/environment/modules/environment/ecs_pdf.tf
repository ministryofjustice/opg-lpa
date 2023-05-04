//----------------------------------
// pdf ECS Service level config

resource "aws_ecs_service" "pdf" {
  name                  = "pdf"
  cluster               = aws_ecs_cluster.online-lpa.id
  task_definition       = aws_ecs_task_definition.pdf.arn
  desired_count         = var.account.autoscaling.pdf.minimum
  launch_type           = "FARGATE"
  platform_version      = "1.3.0"
  propagate_tags        = "TASK_DEFINITION"
  wait_for_steady_state = true
  network_configuration {
    security_groups  = [aws_security_group.pdf_ecs_service.id]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }

  tags = local.pdf_component_tag
}

//----------------------------------
// The service's Security Groups

#tfsec:ignore:aws-ec2-add-description-to-security-group - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "pdf_ecs_service" {
  name_prefix = "${var.environment_name}-pdf-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.pdf_component_tag
}

resource "aws_security_group_rule" "pdf_ecs_service_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:aws-ec2-no-public-egress-sgr - anything out
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.pdf_ecs_service.id
  description       = "PDF ECS to Anywhere - All Traffic"
}

//--------------------------------------
// pdf ECS Service Task level config

resource "aws_ecs_task_definition" "pdf" {
  family                   = "${var.environment_name}-pdf"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 2048
  memory                   = 4096
  container_definitions    = "[${local.pdf_app},  ${local.app_init_container}, ${local.aws_otel_collector}]"
  task_role_arn            = var.ecs_iam_task_roles.pdf.arn
  execution_role_arn       = var.ecs_execution_role.arn
  tags                     = local.pdf_component_tag
  volume {
    name = "app_tmp"
  }
}

data "aws_ecr_repository" "lpa_pdf_app" {
  provider = aws.management
  name     = "online-lpa/pdf_app"
}

//-----------------------------------------------
// pdf ECS Service Task Container level config

locals {

  pdf_app = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "readonlyRootFilesystem" : true,
      "image" : "${data.aws_ecr_repository.lpa_pdf_app.repository_url}:${var.container_version}",
      "mountPoints" : [
        {
          "containerPath" : "/tmp/",
          "sourceVolume" : "app_tmp"
        }
      ],
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
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : "${var.region_name}",
          "awslogs-stream-prefix" : "${var.environment_name}.pdf-app.online-lpa"
        }
      },
      "dependsOn" : [
        {
          "containerName" : "permissions-init",
          "condition" : "SUCCESS"
        }
      ],
      "secrets" : [
        { "name" : "OPG_LPA_PDF_OWNER_PASSWORD", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_pdf_owner_password.name}" }
      ],
      "environment" : [

        { "name" : "OPG_LPA_STACK_NAME", "value" : var.environment_name },
        { "name" : "OPG_DOCKER_TAG", "value" : var.container_version },
        { "name" : "OPG_LPA_STACK_ENVIRONMENT", "value" : var.account_name },
        { "name" : "OPG_LPA_COMMON_APPLICATION_LOG_PATH", "value" : "/var/log/app/application.log" },
        { "name" : "OPG_LPA_COMMON_DYNAMODB_ENDPOINT", "value" : "" },
        { "name" : "OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-locks.name },
        { "name" : "OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-sessions.name },
        { "name" : "OPG_LPA_COMMON_pdf2_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-properties.name },
        { "name" : "OPG_NGINX_SSL_HSTS_AGE", "value" : "31536000" },
        { "name" : "OPG_NGINX_SSL_FORCE_REDIRECT", "value" : "TRUE" },
        { "name" : "OPG_LPA_COMMON_RESQUE_REDIS_HOST", "value" : "redisback" },
        { "name" : "OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET", "value" : data.aws_s3_bucket.lpa_pdf_cache.bucket },
        { "name" : "OPG_LPA_COMMON_PDF_QUEUE_URL", "value" : aws_sqs_queue.pdf_fifo_queue.id }
      ]
    }
  )
}
