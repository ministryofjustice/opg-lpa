output "lambda_function" {
  value = {
    arn           = aws_lambda_function.lambda_function.arn,
    function_name = aws_lambda_function.lambda_function.function_name
  }
}

output "security_group" {
  value = {
    id = aws_security_group.lambda_sg.id
  }
}
