# INFO - Lambda used to manage blocking of IP addresses on the WAF
locals {
  block_ips_lambda_function_name = "block-ips"
}

resource "aws_cloudwatch_log_group" "block_ips_lambda" {
  name              = "/aws/lambda/${local.block_ips_lambda_function_name}"
  retention_in_days = 14
  kms_key_id        = var.application_logs_kms_key_arn
}

data "archive_file" "block_ips_zip" {
  type        = "zip"
  source_dir  = "../../lambdas/block_ips/app"
  output_path = "../../lambdas/block_ips/block_ips.zip"
}

resource "aws_lambda_function" "block_ips_lambda" {
  filename         = data.archive_file.block_ips_zip.output_path
  function_name    = local.block_ips_lambda_function_name
  role             = var.lambda_function_aws_iam_role.arn
  handler          = "block_ips.lambda_handler"
  runtime          = "python3.14"
  depends_on       = [aws_cloudwatch_log_group.block_ips_lambda]
  timeout          = 300
  source_code_hash = filebase64sha256(data.archive_file.block_ips_zip.output_path)
  environment {
    variables = {
      LOG_GROUP_NAME    = var.monitored_log_group_name
      LOG_STREAM_PREFIX = var.monitored_log_stream_prefix
    }
  }
  tracing_config {
    mode = "Active"
  }
}

resource "aws_lambda_permission" "scheduled_block_ip_rule" {
  statement_id  = "AllowExecutionFromScheduledCheck"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.block_ips_lambda.function_name
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.block_ips.arn
  lifecycle {
    replace_triggered_by = [
      aws_lambda_function.block_ips_lambda
    ]
  }
}
