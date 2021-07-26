resource "aws_lambda_function" "lambda_function" {
  function_name = var.lambda_name
  image_uri     = var.image_uri
  package_type  = var.package_type
  role          = aws_iam_role.lambda_role.arn
  timeout       = var.timeout

  image_config {
    entry_point       = var.entry_point
    command           = var.command
    working_directory = var.working_directory
  }

  dynamic "environment" {
    for_each = length(keys(var.environment_variables)) == 0 ? [] : [true]
    content {
      variables = var.environment_variables
    }
  }
  tags = var.tags
}

resource "aws_cloudwatch_log_group" "lambda_function" {
  name = "/aws/lambda/${var.lambda_name}"
  tags = var.tags
}
