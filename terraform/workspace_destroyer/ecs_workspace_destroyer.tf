//----------------------------------
// workspace_destroyer ECS Service level config

resource "aws_ecs_service" "workspace_destroyer" {
  name            = "workspace_destroyer"
  cluster         = aws_ecs_cluster.workspace_destroyer.id
  task_definition = aws_ecs_task_definition.workspace_destroyer.arn
  desired_count   = 0
  launch_type     = "FARGATE"

  network_configuration {
    security_groups = [
      aws_security_group.workspace_destroyer_ecs_service.id,
    ]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }
}


//----------------------------------
// The workspace_destroyer service's Security Groups

resource "aws_security_group" "workspace_destroyer_ecs_service" {
  name_prefix = "workspace_destroyer-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}


//----------------------------------
// Anything out
resource "aws_security_group_rule" "workspace_destroyer_ecs_service_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.workspace_destroyer_ecs_service.id
}

//--------------------------------------
// workspace_destroyer ECS Service Task level config

resource "aws_ecs_task_definition" "workspace_destroyer" {
  family                   = "workspace_destroyer"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 2048
  memory                   = 4096
  container_definitions    = "[${local.workspace_destroyer_app}]"
  task_role_arn            = aws_iam_role.workspace_destroyer_task_role.arn
  execution_role_arn       = aws_iam_role.workspace_destroyer_execution_role.arn
  tags                     = local.default_tags
}


//----------------
// Permissions

resource "aws_iam_role" "workspace_destroyer_task_role" {
  name               = "workspace_destroyer_task_role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.default_tags
}

resource "aws_iam_role_policy_attachment" "base" {
  role       = aws_iam_role.workspace_destroyer_task_role.id
  policy_arn = "arn:aws:iam::aws:policy/ReadOnlyAccess"
}

data "aws_iam_policy" "opg-lpa-ci" {
  arn = "arn:aws:iam::aws:policy/AdministratorAccess"
}


//-----------------------------------------------
// workspace_destroyer ECS Service Task Container level config

locals {
  workspace_destroyer_app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "valveless/python_terraform:${var.container_version}",
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
            "awslogs-group": "${aws_cloudwatch_log_group.workspace_destroyer.name}",
            "awslogs-region": "eu-west-1",
            "awslogs-stream-prefix": "workspace_destroyer"
        }
    }
  }
  EOF
}
