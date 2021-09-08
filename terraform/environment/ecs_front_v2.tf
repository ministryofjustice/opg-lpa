//----------------------------------
// front v2 ECS Service level config

resource "aws_ecs_service" "front_v2" {
  name                  = "front_v2"
  cluster               = aws_ecs_cluster.online-lpa.id
  task_definition       = aws_ecs_task_definition.front_v2.arn
  desired_count         = local.account.autoscaling.front.minimum
  launch_type           = "FARGATE"
  platform_version      = "1.3.0"
  propagate_tags        = "TASK_DEFINITION"
  wait_for_steady_state = true
  network_configuration {
    security_groups  = [aws_security_group.front_ecs_service.id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  depends_on = [aws_lb.front, aws_iam_role.front_task_role, aws_iam_role.execution_role]
  tags       = merge(local.default_tags, local.front_component_tag)
}

//-----------------------------------------------
// Api service discovery

resource "aws_service_discovery_service" "front_v2" {
  name = "front_v2"

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
  front_v2_service_fqdn = "${aws_service_discovery_service.front_v2.name}.${aws_service_discovery_private_dns_namespace.internal.name}"
}


//----------------------------------
// The service's Security Groups

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "front_v2_ecs_service" {
  name_prefix = "${local.environment}-front-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = merge(local.default_tags, local.front_component_tag)
}

// 80 in from the ELB
#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "front_v2_ecs_service_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.front_v2_ecs_service.id
  source_security_group_id = aws_security_group.front_loadbalancer.id
}

// in from service-front-web
#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "front_v2_ecs_service_front_ingress" {
  type                     = "ingress"
  from_port                = 8005
  to_port                  = 8005
  protocol                 = "tcp"
  security_group_id        = aws_security_group.front_v2_ecs_service.id
  source_security_group_id = aws_security_group.front_ecs_service.id
}

// Anything out
#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "front_v2_ecs_service_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:AWS007 - anything out
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_v2_ecs_service.id
}

//--------------------------------------
// front ECS Service Task level config

resource "aws_ecs_task_definition" "front_v2" {
  family                   = "${local.environment}-front_v2"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 256
  memory                   = 512
  container_definitions    = "[${local.front_v2_app}]"
  task_role_arn            = aws_iam_role.front_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = merge(local.default_tags, local.front_component_tag)

}

//----------------
// Permissions

// commented out because we simply use the existing front role
/*resource "aws_iam_role" "front_task_role" {
  name               = "${local.environment}-front-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = merge(local.default_tags, local.front_component_tag)

}*/

// commented out because we simply use the existing front role
/*resource "aws_iam_role_policy" "front_permissions_role" {
  name   = "${local.environment}-frontApplicationPermissions"
  policy = data.aws_iam_policy_document.front_permissions_role.json
  role   = aws_iam_role.front_task_role.id
}*/

/*
  Defines permissions that the application running within the task has.
*/
/*data "aws_iam_policy_document" "front_permissions_role" {
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
}*/

// this is like the one for front, but named v2
data "aws_ecr_repository" "lpa_front_v2_app" {
  provider = aws.management
  name     = "online-lpa/front_v2_app"
}

//-----------------------------------------------
// front ECS Service Task Container level config

// have removed frontweb as doesn't apply

locals {
  front_v2_app = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "image" : "${data.aws_ecr_repository.lpa_front_v2_app.repository_url}:${var.container_version}",
      "mountPoints" : [],
      "name" : "app",
      "portMappings" : [
        {
          "containerPort" : 8005,
          "hostPort" : 8005,
          "protocol" : "tcp"
        }
      ],
      "volumesFrom" : [],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : "eu-west-1",
          "awslogs-stream-prefix" : "${local.environment}.front-v2-app.online-lpa"
        }
      },
      "secrets" : [
        { "name" : "OPG_LPA_FRONT_CSRF_SALT", "valueFrom" : "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.opg_lpa_front_csrf_salt.name}" },
      ],
      "environment" : [
        { "name" : "OPG_LPA_FRONT_NGINX_FRONTENDDOMAIN", "value" : "${local.dns_namespace_env}${local.front_dns}" },
        { "name" : "OPG_NGINX_SERVER_NAMES", "value" : "${local.dns_namespace_env}${local.front_dns} localhost 127.0.0.1" },
        { "name" : "OPG_LPA_FRONT_TRACK_FROM_DATE", "value" : local.track_from_date },
        { "name" : "OPG_LPA_STACK_NAME", "value" : local.environment },
        { "name" : "OPG_DOCKER_TAG", "value" : var.container_version },
        { "name" : "OPG_LPA_STACK_ENVIRONMENT", "value" : local.account_name },
        { "name" : "OPG_LPA_COMMON_APPLICATION_LOG_PATH", "value" : "/var/log/app/application.log" },
        { "name" : "OPG_LPA_COMMON_DYNAMODB_ENDPOINT", "value" : "" },
        { "name" : "OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-locks.name },
        { "name" : "OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-sessions.name },
        { "name" : "OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE", "value" : aws_dynamodb_table.lpa-properties.name },
        { "name" : "OPG_LPA_COMMON_RESQUE_REDIS_HOST", "value" : "redisback" },
        { "name" : "OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET", "value" : data.aws_s3_bucket.lpa_pdf_cache.bucket },
        { "name" : "OPG_LPA_ENDPOINTS_API", "value" : "http://${local.api_service_fqdn}" },
        { "name" : "OPG_LPA_COMMON_REDIS_CACHE_URL", "value" : "tls://${data.aws_elasticache_replication_group.front_cache.primary_endpoint_address}" }
      ]
    }
  )
}
