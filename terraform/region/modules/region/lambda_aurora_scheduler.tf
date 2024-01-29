data "aws_ecr_repository" "aurora_scheduler" {
  provider = aws.management
  name     = "lambda-aurora_scheduler"
}

module "aurora_scheduler" {
  source            = "./modules/lambda_function"
  count             = var.account.always_on_aurora ? 0 : 1
  timeout           = 900
  lambda_name       = "${var.account_name}-aurora-scheduler"
  working_directory = "/"

  image_uri = "${data.aws_ecr_repository.aurora_scheduler.repository_url}:latest"

  ecr_arn                     = data.aws_ecr_repository.aurora_scheduler.arn
  lambda_role_policy_document = data.aws_iam_policy_document.aurora_scheduler_lambda_function_policy[0].json
  log_retention_in_days       = var.account.retention_in_days
}

data "aws_iam_policy_document" "aurora_scheduler_lambda_function_policy" {
  count = var.account.always_on_aurora ? 0 : 1
  statement {
    sid       = "AllowRDSAccess"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "rds:StartDBCluster",
      "rds:StopDBCluster",
      "rds:StopDBInstance",
      "rds:StartDBInstance",
      "rds:DescribeDBClusters"
    ]
  }
}

resource "aws_cloudwatch_event_rule" "start_schedule" {
  count               = var.account.always_on_aurora ? 0 : 1
  name                = "${var.account_name}-aurora-start"
  description         = "Cron to start development Aurora clusters"
  schedule_expression = "cron(0 05 ? * * *)" # 5am UTC, every day
}

resource "aws_cloudwatch_event_rule" "stop_schedule" {
  count               = var.account.always_on_aurora ? 0 : 1
  name                = "${var.account_name}-aurora-stop"
  description         = "Cron to stop development Aurora clusters"
  schedule_expression = "cron(0 21 ? * * *)" # 9pm UTC, every day
}

resource "aws_cloudwatch_event_target" "start_schedule_lambda" {
  count     = var.account.always_on_aurora ? 0 : 1
  rule      = aws_cloudwatch_event_rule.start_schedule[0].name
  target_id = "lambda_function"
  arn       = one(module.aurora_scheduler[*].lambda_function.arn)
  input     = "{\"command\":\"start\"}"
}

resource "aws_cloudwatch_event_target" "stop_schedule_lambda" {
  count     = var.account.always_on_aurora ? 0 : 1
  rule      = aws_cloudwatch_event_rule.stop_schedule[0].name
  target_id = "lambda_function"
  arn       = one(module.aurora_scheduler[*].lambda_function.arn)
  input     = "{\"command\":\"stop\"}"
}

resource "aws_lambda_permission" "allow_events_bridge_start_lambda" {
  count         = var.account.always_on_aurora ? 0 : 1
  statement_id  = "AllowStartExecutionFromCloudWatch"
  action        = "lambda:InvokeFunction"
  function_name = one(module.aurora_scheduler[*].lambda_function.function_name)
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.start_schedule[0].arn
}

resource "aws_lambda_permission" "allow_events_bridge_stop_lambda" {
  count         = var.account.always_on_aurora ? 0 : 1
  statement_id  = "AllowStopExecutionFromCloudWatch"
  action        = "lambda:InvokeFunction"
  function_name = one(module.aurora_scheduler[*].lambda_function.function_name)
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.stop_schedule[0].arn
}
