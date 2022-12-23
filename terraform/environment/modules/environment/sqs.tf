resource "aws_sqs_queue" "pdf_fifo_queue" {
  name                              = "lpa-pdf-queue-${var.environment_name}.fifo"
  message_retention_seconds         = "3600"
  visibility_timeout_seconds        = "90"
  fifo_queue                        = true
  content_based_deduplication       = true
  kms_master_key_id                 = "alias/mrk_pdf_sqs_encryption_key-${var.account_name}"
  kms_data_key_reuse_period_seconds = "300"
  max_message_size                  = "262144"
  tags                              = local.pdf_component_tag

  depends_on = [aws_ecs_service.api]
}

resource "aws_sqs_queue_policy" "queue_policy" {
  queue_url  = aws_sqs_queue.pdf_fifo_queue.id
  policy     = data.aws_iam_policy_document.queue_policy_document.json
  depends_on = [aws_ecs_service.api]
}

data "aws_iam_policy_document" "queue_policy_document" {
  statement {
    principals {
      type        = "AWS"
      identifiers = [var.ecs_iam_task_roles.api.arn]
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
      identifiers = [var.ecs_iam_task_roles.pdf.arn]
    }

    effect    = "Allow"
    resources = [aws_sqs_queue.pdf_fifo_queue.arn]

    actions = [
      "sqs:DeleteMessage",
      "sqs:ReceiveMessage",
    ]
  }
}
