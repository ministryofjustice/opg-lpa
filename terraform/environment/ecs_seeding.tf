//----------------------------------
// The Api service's Security Groups

resource "aws_security_group" "seeding_ecs_service" {
  count       = local.environment == "production" ? 0 : 1
  name_prefix = "${terraform.workspace}-seeding-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "seeding_ecs_service_egress" {
  count             = local.environment == "production" ? 0 : 1
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.seeding_ecs_service.id
}

//--------------------------------------
// seeding ECS Service Task level config

resource "aws_ecs_task_definition" "seeding" {
  count                    = local.environment == "production" ? 0 : 1
  family                   = "${terraform.workspace}-seeding"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 2048
  memory                   = 4096
  container_definitions    = "[${local.seeding_app}]"
  task_role_arn            = aws_iam_role.seeding_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}


//----------------
// Permissions

resource "aws_iam_role" "seeding_task_role" {
  count              = local.environment == "production" ? 0 : 1
  name               = "${local.environment}-seeding-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.default_tags
}

data "aws_ecr_repository" "lpa_seeding_app" {
  provider = aws.management
  name     = "online-lpa/seeding_app"
}

//-----------------------------------------------
// seeding ECS Service Task Container level config

locals {
  seeding_app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.lpa_seeding_app.repository_url}:${var.container_version}",
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
            "awslogs-stream-prefix": "${local.environment}.seeding.online-lpa"
        }
    },
    "secrets": [
      { "name": "OPG_LPA_POSTGRES_USERNAME", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_username.name}" },
      { "name": "OPG_LPA_POSTGRES_PASSWORD", "valueFrom": "/aws/reference/secretsmanager/${data.aws_secretsmanager_secret.api_rds_password.name}" }
    ],
    "environment": [
      { "name": "OPG_LPA_POSTGRES_NAME", "value": "${aws_db_instance.api.name}"},
      { "name": "OPG_LPA_POSTGRES_HOSTNAME", "value": "${aws_db_instance.api.address}"},
      { "name": "OPG_LPA_POSTGRES_PORT", "value": "${aws_db_instance.api.port}"},
      { "name": "OPG_LPA_STACK_ENVIRONMENT", "value" : "${local.environment}"}
      ]
    }
  EOF
}
