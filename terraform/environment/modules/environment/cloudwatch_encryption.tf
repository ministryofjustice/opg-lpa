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

resource "aws_kms_key_policy" "application_log_group_cloudwatch_logs" {
  key_id = data.aws_kms_alias.application_log_group_encryption_alias.target_key_id
  policy = data.aws_iam_policy_document.application_log_group_cloudwatch_logs.json
}


data "aws_iam_policy_document" "application_log_group_cloudwatch_logs" {
  statement {
    sid    = "Allow CloudWatch access"
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
      values   = ["arn:aws:logs:${data.aws_region.current.name}:${data.aws_caller_identity.current.account_id}:log-group/*"]
    }
  }
}
