locals {
  # for an arn like arn:aws:ecs:eu-west-1:<account-id>:task-definition/<environment>-api:1510, replace :1510 with :*
  aws_ecs_task_definition_api_arn = "${trimsuffix(module.eu-west-1.aws_ecs_task_definition_api_arn, regex(module.eu-west-1.aws_ecs_task_definition_api_arn, "/^:\\d{1,4}?$/"))}:*"
  api_task_definition_arns        = local.dr_enabled ? [local.aws_ecs_task_definition_api_arn] : compact(concat([local.aws_ecs_task_definition_api_arn], try([module.eu-west-2[0].aws_ecs_task_definition_api_arn], [])))
}
//----------------
// API IAM ECS task role

resource "aws_iam_role" "api_task_role" {
  name               = "${local.environment_name}-api-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.api_component_tag
}


// ----------------
// Admin ECS task role
//----------------

resource "aws_iam_role" "admin_task_role" {
  name               = "${local.environment_name}-admin-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.admin_component_tag
}

//----------------
// Front ECS task role
//----------------

resource "aws_iam_role" "front_task_role" {
  name               = "${local.environment_name}-front-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.front_component_tag

}

//----------------
// PDF ECS task role

resource "aws_iam_role" "pdf_task_role" {
  name               = "${local.environment_name}-pdf-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.pdf_component_tag
}


//----------------
// Seed ECS task role
resource "aws_iam_role" "seeding_task_role" {
  name               = "${local.environment_name}-seeding-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.seeding_component_tag
}

//------------------------------------------------
// ECS API Cron task role

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
  name               = "${local.environment_name}-cloudwatch_events_ecs_cron"
  assume_role_policy = data.aws_iam_policy_document.cloudwatch_events_assume_policy.json
  tags               = local.api_component_tag
}

data "aws_iam_policy_document" "cloudwatch_events_role_policy" {
  statement {
    effect    = "Allow"
    actions   = ["ecs:RunTask"]
    resources = local.api_task_definition_arns
  }
  statement {
    effect  = "Allow"
    actions = ["iam:PassRole"]

    # This needs both the execution role and the task role.
    resources = [
      aws_iam_role.execution_role.arn,
      aws_iam_role.api_task_role.arn,
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
  role   = aws_iam_role.cloudwatch_events_ecs_role.name
  policy = data.aws_iam_policy_document.cloudwatch_events_role_policy.json
}
