resource "aws_cloudwatch_event_rule" "block_ips" {
  name                = "block-ips"
  description         = "Execute the blocking of malicious IPs"
  schedule_expression = "rate(5 minutes)"
  state               = var.waf_ip_blocking_enabled ? "ENABLED" : "DISABLED"
}

resource "aws_cloudwatch_event_target" "block_ips" {
  target_id = "block-ips"
  arn       = aws_lambda_function.block_ips_lambda.arn
  rule      = aws_cloudwatch_event_rule.block_ips.name
}
