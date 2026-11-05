#tfsec:ignore:aws-lambda-enable-tracing - To be enabled along with apps
resource "aws_lambda_function" "lambda_function" {
  function_name = "${var.lambda_name}-${var.environment}"
  image_uri     = var.image_uri
  package_type  = var.package_type
  role          = aws_iam_role.lambda_role.arn
  timeout       = var.timeout

  image_config {
    entry_point       = var.entry_point
    command           = var.command
    working_directory = var.working_directory
  }

  vpc_config {
    subnet_ids         = var.vpc_subnet_ids
    security_group_ids = [aws_security_group.lambda_sg.id]
  }

  dynamic "environment" {
    for_each = length(keys(var.environment_variables)) == 0 ? [] : [true]
    content {
      variables = var.environment_variables
    }
  }
  tags = var.tags
}

#tfsec:ignore:aws-cloudwatch-log-group-customer-key encryption of logs too expensive
resource "aws_cloudwatch_log_group" "lambda_function" {
  name              = "/aws/lambda/${var.environment}-${var.lambda_name}"
  tags              = var.tags
  retention_in_days = var.log_retention_in_days
}

resource "aws_security_group" "lambda_sg" {
  description = "Lambda security group"
  name_prefix = "${var.environment}-${var.lambda_name}"
  vpc_id      = var.vpc_id
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "lambda_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:aws-ec2-no-public-egress-sgr - anything out
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.lambda_sg.id
  description       = "Lambda to Anywhere - All Traffic"
}
