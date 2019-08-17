resource "aws_sqs_queue" "workspace_destroyer" {
  count            = local.account_name == "development" ? 1 : 0
  name             = "${local.account_name}-opg-lpa-workspace-destroyer-queue"
  max_message_size = 2048
  tags             = local.default_tags
}

resource "local_file" "queue_config" {
  count    = local.account_name == "development" ? 1 : 0
  content  = "${jsonencode(local.queue_config)}"
  filename = "/tmp/queue_config.json"
}

locals {
  queue_config = {
    account_id                  = local.account_id
    workspace_destory_queue_url = aws_sqs_queue.workspace_destroyer[0].id
  }
}

resource "aws_iam_role" "iam_for_workspace_destroyer_lambda" {
  name               = "iam_for_workspace_destroyer_lambda"
  assume_role_policy = data.aws_iam_policy_document.lambda_assume_role_policy.json
  tags               = local.default_tags
}

data "aws_iam_policy_document" "lambda_assume_role_policy" {
  statement {
    actions = ["sts:AssumeRole"]
    principals {
      type        = "Service"
      identifiers = ["lambda.amazonaws.com"]
    }
  }
}

resource "aws_iam_role_policy" "iam_for_workspace_destroyer_lambda_execution_role" {
  name   = "WorkspaceDeestroyerPermissions"
  policy = data.aws_iam_policy_document.iam_for_workspace_destroyer_lambda_inline_execution_role.json
  role   = aws_iam_role.iam_for_workspace_destroyer_lambda.id
}

data "aws_iam_policy_document" "iam_for_workspace_destroyer_lambda_inline_execution_role" {
  statement {
    actions = [
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
      "sqs:GetQueueAttributes",
    ]
    resources = [
      aws_sqs_queue.workspace_destroyer[0].arn,
    ]
  }
}

resource "aws_lambda_function" "workspace_destroyer" {
  filename         = "/tmp/lambda_function_payload.zip"
  function_name    = "workspace_destroyer"
  role             = aws_iam_role.iam_for_workspace_destroyer_lambda.arn
  handler          = "service.lambda_handler"
  source_code_hash = "${filebase64sha256("/tmp/lambda_function_payload.zip")}"
  runtime          = "python3.7"
  timeout          = 900
  memory_size      = 128
  # TODO: provide credentials for terraform to the lambda function
  environment {
    variables = {
      GIT_URL           = "https://github.com/ministryofjustice/opg-lpa.git"
      REPO_DIR          = "/tmp/opg-lpa"
      TF_CONFIG_PATH    = "terraform/terraform_environment"
      TERRAFORM_VERSION = "0.12.5"
    }
  }
  tags = local.default_tags
}
