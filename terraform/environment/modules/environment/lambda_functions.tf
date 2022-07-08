data "aws_ecr_repository" "performance_platform_worker" {
  provider = aws.management
  name     = "perfplat-worker"
}

module "performance_platform_worker" {
  source            = "./modules/lambda_function"
  count             = var.account.performance_platform_enabled == true ? 1 : 0
  lambda_name       = "${var.environment_name}-perfplat-worker" #limited to 32 chars hence shortened
  description       = "Function to take Cloudwatch Logs Subscription Filters and send them to SQS"
  working_directory = "/var/task"
  environment_variables = {
    "OPG_LPA_POSTGRES_USERNAME" : data.aws_secretsmanager_secret_version.performance_platform_db_username.secret_string,
    "OPG_LPA_POSTGRES_PASSWORD" : data.aws_secretsmanager_secret_version.performance_platform_db_password.secret_string,
    "OPG_LPA_POSTGRES_HOSTNAME" : local.db.endpoint,
    "OPG_LPA_POSTGRES_PORT" : local.db.port,
    "OPG_LPA_POSTGRES_NAME" : local.db.name
  }
  image_uri = "${data.aws_ecr_repository.performance_platform_worker.repository_url}:${var.lambda_container_version}"

  ecr_arn                     = data.aws_ecr_repository.performance_platform_worker.arn
  lambda_role_policy_document = data.aws_iam_policy_document.performance_platform_worker_lambda_function_policy[0].json
  tags                        = local.performance_platform_component_tag
}

data "aws_iam_policy_document" "performance_platform_worker_lambda_function_policy" {
  count = var.account.performance_platform_enabled == true ? 1 : 0
  statement {
    sid       = "AllowSQSAccess"
    effect    = "Allow"
    resources = [aws_sqs_queue.performance_platform_worker[0].arn]
    actions = [
      "sqs:SendMessage",
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
      "sqs:GetQueueAttributes",
    ]
  }
}
