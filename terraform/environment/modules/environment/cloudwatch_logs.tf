#tfsec:ignore:aws-cloudwatch-log-group-customer-key encryption of logs too expensive
resource "aws_cloudwatch_log_group" "application_logs" {
  depends_on = [aws_kms_key_policy.application_log_group_cloudwatch_logs]

  name              = "${var.environment_name}_application_logs"
  retention_in_days = var.account.log_retention_in_days
  kms_key_id        = data.aws_kms_alias.application_log_group_encryption_alias.target_key_arn

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
resource "aws_cloudwatch_query_definition" "all_error_logs_query" {
  name            = "${var.environment_name}/all error logs query"
  log_group_names = [aws_cloudwatch_log_group.application_logs.name]

  query_string = <<-EOF
  fields @timestamp, service_name, level, msg, trace_id, user_id, http_method
    |filter level in ['ERROR', 'CRITICAL']
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

resource "aws_cloudwatch_query_definition" "migrations" {
  name            = "${var.environment_name}/migrations"
  log_group_names = [aws_cloudwatch_log_group.application_logs.name]

  query_string = <<-EOF
  fields @timestamp, @message
  | filter @logStream like "migrations"
  |limit 10000
  EOF
}

data "aws_iam_policy_document" "application_log_group_cloudwatch_logs" {
  statement {
    sid    = "AllowCloudWatchLogsService"
    effect = "Allow"
    principals {
      type        = "Service"
      identifiers = ["logs.${data.aws_region.current.name}.amazonaws.com"]
    }
    actions = [
      "kms:Encrypt*",
      "kms:Decrypt*",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:Describe*",
    ]
    resources = ["*"]
    condition {
      test     = "ArnLike"
      variable = "kms:EncryptionContext:aws:logs:arn"
      values   = ["arn:aws:logs:${data.aws_region.current.name}:${data.aws_caller_identity.current.account_id}:*"]
    }
  }
  statement {
    effect = "Allow"
    sid    = "AllowKMSKeyUsageForCloudWatchLogs"
    resources = [
      data.aws_kms_alias.application_log_group_encryption_alias.target_key_arn,
    ]
  }
}

resource "aws_kms_key_policy" "application_log_group_cloudwatch_logs" {
  key_id = data.aws_kms_alias.application_log_group_encryption_alias.target_key_id
  policy = data.aws_iam_policy_document.application_log_group_cloudwatch_logs.json
}
