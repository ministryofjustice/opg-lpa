resource "aws_sqs_queue" "workspace_destroyer" {
  count                      = local.account_name == "development" ? 1 : 0
  name                       = "${local.account_name}-opg-lpa-workspace-destroyer-queue"
  max_message_size           = 2048
  visibility_timeout_seconds = 900
  tags                       = local.default_tags
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
  count              = local.account_name == "development" ? 1 : 0
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

resource "aws_iam_role_policy_attachment" "aws_lambda_vpc_access_execution_role" {
  count      = local.account_name == "development" ? 1 : 0
  role       = aws_iam_role.iam_for_workspace_destroyer_lambda[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}

resource "aws_iam_role_policy" "iam_for_workspace_destroyer_lambda_execution_role" {
  count  = local.account_name == "development" ? 1 : 0
  name   = "WorkspaceDeestroyerPermissions"
  policy = data.aws_iam_policy_document.iam_for_workspace_destroyer_lambda_inline_execution_role.json
  role   = aws_iam_role.iam_for_workspace_destroyer_lambda[0].id
}

data "aws_iam_policy_document" "iam_for_workspace_destroyer_lambda_inline_execution_role" {
  statement {
    sid = "QueueAccess"
    actions = [
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
      "sqs:GetQueueAttributes",
    ]
    resources = [
      aws_sqs_queue.workspace_destroyer[0].arn,
    ]
  }
  statement {
    sid = "CloudwatchLogging"
    actions = [
      "logs:CreateLogGroup",
      "logs:CreateLogStream",
      "logs:PutLogEvents",
      "logs:DescribeLogStreams",
    ]

    resources = [
      "arn:aws:logs:::*",
    ]
  }
  statement {
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "xray:PutTraceSegments",
      "xray:PutTelemetryRecords",
      "xray:GetSamplingRules",
      "xray:GetSamplingTargets",
      "xray:GetSamplingStatisticSummaries",
    ]
  }
}

resource "aws_lambda_function" "workspace_destroyer" {
  count                          = local.account_name == "development" ? 1 : 0
  filename                       = "/tmp/lambda_function_payload.zip"
  function_name                  = "workspace_destroyer"
  role                           = aws_iam_role.iam_for_workspace_destroyer_lambda[0].arn
  handler                        = "service.lambda_handler"
  source_code_hash               = "${filebase64sha256("/tmp/lambda_function_payload.zip")}"
  runtime                        = "python3.7"
  timeout                        = 900
  memory_size                    = 128
  reserved_concurrent_executions = 1
  # https://github.com/lambci/git-lambda-layer
  layers = ["arn:aws:lambda:eu-west-1:553035198032:layer:git:6"]
  tracing_config {
    mode = "Active"
  }
  # TODO: provide credentials for terraform to the lambda function
  environment {
    variables = {
      GIT_URL           = "https://github.com/ministryofjustice/opg-lpa.git"
      REPO_DIR          = "/tmp/opg-lpa"
      TF_CONFIG_PATH    = "terraform/terraform_environment"
      TERRAFORM_VERSION = "0.12.5"
      TEST              = "True"
    }
  }
  tags = local.default_tags
}

resource "aws_lambda_event_source_mapping" "workspace_destroyer" {
  count            = local.account_name == "development" ? 1 : 0
  event_source_arn = "${aws_sqs_queue.workspace_destroyer[0].arn}"
  function_name    = "${aws_lambda_function.workspace_destroyer[0].arn}"
}
