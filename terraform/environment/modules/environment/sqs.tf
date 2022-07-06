resource "aws_sqs_queue" "pdf_fifo_queue" {
  name                              = "lpa-pdf-queue-${var.environment_name}.fifo"
  message_retention_seconds         = "3600"
  visibility_timeout_seconds        = "90"
  fifo_queue                        = true
  content_based_deduplication       = true
  kms_master_key_id                 = "alias/mrk_pdf_sqs_encryption_key-${var.account_name}"
  kms_data_key_reuse_period_seconds = "300"
  max_message_size                  = "262144"
  tags                              = merge(local.default_opg_tags, local.pdf_component_tag)

  depends_on = [aws_ecs_service.api, aws_iam_role.api_task_role, aws_iam_role.pdf_task_role]
}

resource "aws_sqs_queue_policy" "queue_policy" {
  queue_url  = aws_sqs_queue.pdf_fifo_queue.id
  policy     = data.aws_iam_policy_document.queue_policy_document.json
  depends_on = [aws_ecs_service.api, aws_iam_role.api_task_role, aws_iam_role.pdf_task_role]
}

data "aws_iam_policy_document" "queue_policy_document" {
  statement {
    principals {
      type        = "AWS"
      identifiers = [aws_iam_role.api_task_role.arn]
    }

    effect    = "Allow"
    resources = [aws_sqs_queue.pdf_fifo_queue.arn]

    actions = [
      "sqs:GetQueueAttributes",
      "sqs:SendMessage",
    ]
  }

  statement {
    principals {
      type        = "AWS"
      identifiers = [aws_iam_role.pdf_task_role.arn]
    }

    effect    = "Allow"
    resources = [aws_sqs_queue.pdf_fifo_queue.arn]

    actions = [
      "sqs:DeleteMessage",
      "sqs:ReceiveMessage",
    ]
  }
}


resource "aws_sqs_queue" "performance_platform_worker" {
  name                              = "lpa-performance-platform-worker-queue-${var.environment_name}"
  count                             = var.account.performance_platform_enabled == true ? 1 : 0
  delay_seconds                     = 90
  max_message_size                  = 16384 #adjust as needed
  message_retention_seconds         = 86400
  receive_wait_time_seconds         = 10
  tags                              = merge(local.default_opg_tags, local.performance_platform_component_tag)
  kms_master_key_id                 = "alias/mrk_perfplat_sqs_encryption_key-${var.account_name}"
  kms_data_key_reuse_period_seconds = "300"

}

resource "aws_sqs_queue_policy" "performance_platform_worker" {
  count      = var.account.performance_platform_enabled == true ? 1 : 0
  queue_url  = aws_sqs_queue.performance_platform_worker[0].id
  policy     = data.aws_iam_policy_document.performance_platform_worker[0].json
  depends_on = [aws_ecs_service.api, aws_iam_role.api_task_role]
}

data "aws_iam_policy_document" "performance_platform_worker" {
  count = var.account.performance_platform_enabled == true ? 1 : 0
  statement {
    effect    = "Allow"
    resources = [aws_sqs_queue.performance_platform_worker[0].arn]
    actions = [
      "sqs:ChangeMessageVisibility",
      "sqs:DeleteMessage",
      "sqs:GetQueueAttributes",
      "sqs:GetQueueUrl",
      "sqs:ListQueueTags",
      "sqs:ReceiveMessage",
      "sqs:SendMessage",
    ]
    principals {
      type        = "AWS"
      identifiers = [var.account.account_id]
    }
  }
}

resource "aws_lambda_event_source_mapping" "performance_platform_worker" {
  count            = var.account.performance_platform_enabled == true ? 1 : 0
  event_source_arn = aws_sqs_queue.performance_platform_worker[0].arn
  function_name    = module.performance_platform_worker[0].lambda_function.arn
}
