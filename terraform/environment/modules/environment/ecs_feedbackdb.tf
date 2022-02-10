//----------------------------------
// The Api service's Security Groups
#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "feedbackdb_ecs_service" {
  name_prefix = "${terraform.workspace}-feedbackdb-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = merge(local.default_opg_tags, local.feedbackdb_component_tag)
}

//----------------------------------
// Anything out except production
#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "feedbackdb_ecs_service_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:AWS007 - anything out
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.feedbackdb_ecs_service.id
}

//--------------------------------------
// feedbackdb ECS Service Task level config

resource "aws_ecs_task_definition" "feedbackdb" {
  family                   = "${terraform.workspace}-feedbackdb"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 2048
  memory                   = 4096
  container_definitions    = "[${local.feedbackdb_app}]"
  task_role_arn            = aws_iam_role.feedbackdb_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = merge(local.default_opg_tags, local.feedbackdb_component_tag)
}


//----------------
// Permissions

resource "aws_iam_role" "feedbackdb_task_role" {
  name               = "${var.environment_name}-feedbackdb-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = merge(local.default_opg_tags, local.feedbackdb_component_tag)
}

data "aws_ecr_repository" "lpa_feedbackdb_app" {
  provider = aws.management
  name     = "online-lpa/feedbackdb_app"
}

//-----------------------------------------------
// feedbackdb ECS Service Task Container level config

locals {
  feedbackdb_app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.lpa_feedbackdb_app.repository_url}:${var.container_version}",
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
            "awslogs-group": "${aws_cloudwatch_log_group.application_logs.name}",
            "awslogs-region": "${var.region_name}",
            "awslogs-stream-prefix": "${var.environment_name}.feedbackdb.online-lpa"
        }
    },
    "secrets": [
      { "name": "OPG_LPA_POSTGRES_USERNAME", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_username.name}" },
      { "name": "OPG_LPA_POSTGRES_PASSWORD", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_password.name}" },
      { "name": "OPG_LPA_POSTGRES_FEEDBACK_USERNAME", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.performance_platform_db_username.name}" },
      { "name": "OPG_LPA_POSTGRES_FEEDBACK_PASSWORD", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.performance_platform_db_password.name}" }
    ],
    "environment": [
      { "name": "OPG_LPA_POSTGRES_NAME", "value": "${local.db.name}"},
      { "name": "OPG_LPA_POSTGRES_HOSTNAME", "value": "${local.db.endpoint}"},
      { "name": "OPG_LPA_POSTGRES_PORT", "value": "${local.db.port}"},
      { "name": "OPG_LPA_STACK_ENVIRONMENT", "value" : "${var.account_name}"}
      ]
    }
  EOF
}
