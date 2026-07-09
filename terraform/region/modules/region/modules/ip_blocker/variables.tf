variable "dynamodb_kms_key_arn" {
  type = string
}

variable "lambda_function_aws_iam_role" {
  type = any
}

variable "waf_ip_blocking_enabled" {
  type = bool
}
