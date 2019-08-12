
//------------------------------------------------
// General Permissions

// What services can assume the role
data "aws_iam_policy_document" "cloudwatch_events_assume_policy" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]
    principals {
      identifiers = ["events.amazonaws.com"]
      type        = "Service"
    }
  }
}

resource "aws_iam_role" "cloudwatch_events_ecs_role" {
  name               = "${local.environment}-cloudwatch_events_ecs_cron"
  assume_role_policy = data.aws_iam_policy_document.cloudwatch_events_assume_policy.json
}

//---

data "aws_iam_policy_document" "cloudwatch_events_role_policy" {
  statement {
    effect  = "Allow"
    actions = ["ecs:RunTask"]
    resources = [
      aws_ecs_task_definition.api.arn
    ]
  }
  statement {
    effect  = "Allow"
    actions = ["iam:PassRole"]

    # This needs both the execution role and the task role.
    resources = [
      aws_iam_role.execution_role.arn,
      aws_iam_role.api_task_role.arn
    ]
    condition {
      test     = "StringLike"
      values   = ["ecs-tasks.amazonaws.com"]
      variable = "iam:PassedToService"
    }
  }
}

// The assumed role's permissions
resource "aws_iam_role_policy" "ecs_events_run_task_with_any_role" {
  role   = aws_iam_role.cloudwatch_events_ecs_role.id
  policy = data.aws_iam_policy_document.cloudwatch_events_role_policy.json
}

//------------------------------------------------
// Trigger times

resource "aws_cloudwatch_event_rule" "middle_of_the_night" {
  name                = "${local.environment}-middle-of-the-night-cron"
  schedule_expression = "cron(0 3 * * ? *)" // 3am UTC, every day.
}

resource "aws_cloudwatch_event_rule" "mid_morning" {
  name                = "${local.environment}-mid-morning-cron"
  schedule_expression = "cron(0 10 * * ? *)" // 10am UTC, every day.
}

//------------------------------------------------
// Task definition for Cron

resource "aws_ecs_task_definition" "api_crons" {
  family                   = "${terraform.workspace}-api-crons"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.api_app}]"
  task_role_arn            = "${aws_iam_role.api_task_role.arn}"
  execution_role_arn       = "${aws_iam_role.execution_role.arn}"
  tags                     = "${local.default_tags}"
}


//------------------------------------------------
// Account Cleanup Task

resource "aws_cloudwatch_event_target" "api_ecs_cron_event_account_cleanup" {
  count = local.account_name != "development" ? 0 : 1
  # don't run crons on preprod and prod
  # This will need to change to
  //count = local.account_name == "preproduction" ? 0 : 1
  # don't run crons on preprod

  target_id = "account-cleanup"
  arn       = aws_ecs_cluster.online-lpa.arn
  rule      = aws_cloudwatch_event_rule.mid_morning.name
  role_arn  = aws_iam_role.cloudwatch_events_ecs_role.arn

  ecs_target {
    task_count          = 1
    task_definition_arn = aws_ecs_task_definition.api.arn
    launch_type         = "FARGATE"
    platform_version    = "LATEST"

    network_configuration {
      security_groups = [
        aws_security_group.api_ecs_service.id,
        aws_security_group.rds-client.id,
      ]
      subnets          = data.aws_subnet_ids.private.ids
      assign_public_ip = false
    }
  }

  input = <<DOC
{
  "containerOverrides": [
    {
      "name": "app",
      "command": ["php", "/app/public/index.php", "account-cleanup"]
    }
  ]
}
DOC
}

//------------------------------------------------
// Generate Stats Task

resource "aws_cloudwatch_event_target" "api_ecs_cron_event_generate_stats" {
  target_id = "generate-stats"
  arn = aws_ecs_cluster.online-lpa.arn
  rule = aws_cloudwatch_event_rule.middle_of_the_night.name
  role_arn = aws_iam_role.cloudwatch_events_ecs_role.arn

  ecs_target {
    task_count = 1
    task_definition_arn = aws_ecs_task_definition.api.arn
    launch_type = "FARGATE"
    platform_version = "LATEST"

    network_configuration {
      security_groups = [
        aws_security_group.api_ecs_service.id,
        aws_security_group.rds-client.id,
      ]
      subnets = data.aws_subnet_ids.private.ids
      assign_public_ip = false
    }
  }

  input = <<DOC
{
  "containerOverrides": [
    {
      "name": "app",
      "command": ["php", "/app/public/index.php", "generate-stats"]
    }
  ]
}
DOC
}
