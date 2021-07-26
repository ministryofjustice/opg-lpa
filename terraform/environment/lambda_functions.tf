data "aws_ecr_repository" "perfplat_worker" {
  name = "${local.environment}/perfplat-worker"
}

module "perfplat_worker"{
  source            = "./modules/lambda_function"
  count             = local.account.performance_platform_enabled == true ? 1 : 0
  lambda_name       = "clsf-to-sqs"
  description       = "Function to take Cloudwatch Logs Subscription Filters and send them to SQS"
  working_directory = "/var/task"
  environment_variables = {

  }
  image_uri = "${data.aws_ecr_repository.perfplat_worker}:${var.lambda_container_version}"

  ecr_arn                     = data.aws_ecr_repository.perfplat_worker.arn
  lambda_role_policy_document = data.aws_iam_policy_document.perfplat_worker_lambda_function_policy[0].json
  tags                        = local.default_tags
}

data "aws_iam_policy_document" "perfplat_worker_lambda_function_policy" {
  count = local.account.performance_platform_enabled == true ? 1 : 0
  statement {
    sid       = "AllowSQSAccess"
    effect    = "Allow"
    resources = [aws_sqs_queue.perplat_worker[0].arn]
    actions = [
      "sqs:SendMessage",
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
    ]
  }
}
