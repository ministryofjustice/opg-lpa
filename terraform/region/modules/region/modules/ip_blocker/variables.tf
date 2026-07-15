variable "dynamodb_kms_key_arn" {
  type = string
}

variable "application_logs_kms_key_arn" {
  type = string
}

variable "lambda_function_aws_iam_role" {
  type = any
}

variable "waf_ip_blocking_enabled" {
  type = bool
}

variable "monitored_log_group_name" {
  type = string
}

variable "monitored_log_stream_prefix" {
  type = string
}
