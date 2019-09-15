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
resource "aws_iam_role_policy_attachment" "aws_lambda_vpc_access_execution_role" {
  role       = aws_iam_role.iam_for_workspace_destroyer_lambda.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}
resource "aws_iam_role_policy" "iam_for_workspace_destroyer_lambda_execution_role" {
  name   = "WorkspaceDeestroyerPermissions"
  policy = data.aws_iam_policy_document.iam_for_workspace_destroyer_lambda_inline_execution_role.json
  role   = aws_iam_role.iam_for_workspace_destroyer_lambda.id
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
      aws_sqs_queue.workspace_destroyer.arn,
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
  filename                       = "/tmp/lambda_function_payload.zip"
  function_name                  = "workspace_destroyer"
  role                           = aws_iam_role.iam_for_workspace_destroyer_lambda.arn
  handler                        = "service.lambda_handler"
  source_code_hash               = "${filebase64sha256("/tmp/lambda_function_payload.zip")}"
  runtime                        = "python3.7"
  timeout                        = 900
  memory_size                    = 128
  reserved_concurrent_executions = 1
  tracing_config {
    mode = "Active"
  }
  # TODO: provide credentials for terraform to the lambda function
  environment {
    variables = {
      TERRAFORM_VERSION = "0.12.8"
      TEST              = "True"
    }
  }
  tags = local.default_tags
}
resource "aws_lambda_event_source_mapping" "workspace_destroyer" {
  event_source_arn = "${aws_sqs_queue.workspace_destroyer.arn}"
  function_name    = "${aws_lambda_function.workspace_destroyer.arn}"
}

