#tfsec:ignore:aws-cloudwatch-log-group-customer-key encryption of logs too expensive
resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${var.environment_name}_application_logs"
  retention_in_days = var.account.log_retention_in_days

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "${var.environment_name}_application_logs"
    },
  )
}
resource "aws_cloudwatch_query_definition" "error_insight_query" {
  name            = "${var.environment_name}/error insight query"
  log_group_names = [aws_cloudwatch_log_group.application_logs.name]

  query_string = <<-EOF
  fields @timestamp, service_name, level, msg, trace_id, user_id, http_method
    |filter level in ['ERROR', 'CRITICAL']
    |filter ispresent (trace_id)
    |stats count() as error_count,
      latest (msg) as latest_error_message,
      latest(trace_id) as last_trace_id,
      latest(@timestamp) as last_error_time
      by service_name, error_code, http_method
    |sort error_count desc
    |limit 10000
  EOF
}

resource "aws_cloudwatch_log_metric_filter" "application_5xx_errors" {
  name           = "${var.environment_name}-5xx-errors"
  pattern        = "{ $.status = 5* }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${var.environment_name}-5xx-errors"
    namespace     = "Make/Monitoring"
    value         = "1"
    default_value = "0"
  }
}

resource "aws_cloudwatch_log_metric_filter" "application_40x_errors" {
  name           = "${var.environment_name}-40x-auth-errors"
  pattern        = "{ ( $.status = 401 ) || ( $.status = 403 ) }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${var.environment_name}-40x-errors"
    namespace     = "Make/Monitoring"
    value         = "1"
    default_value = "0"
  }
}
