resource "aws_sqs_queue" "pdf_fifo_queue" {
  name                              = "lpa-pdf-queue-${local.environment}.fifo"
  message_retention_seconds         = "3600"
  visibility_timeout_seconds        = "90"
  fifo_queue                        = true
  content_based_deduplication       = false
  kms_master_key_id                 = "alias/aws/sqs"
  kms_data_key_reuse_period_seconds = "300"
  max_message_size                  = "262144"
  tags                              = local.default_tags
  # depends_on = [
  #   aws_autoscaling_group.api2,
  #   aws_autoscaling_group.pdf2,
  # ]
}

resource "aws_sqs_queue_policy" "queue_policy" {
  queue_url = aws_sqs_queue.pdf_fifo_queue.id
  policy    = data.aws_iam_policy_document.queue_policy_document.json
}

data "aws_iam_policy_document" "queue_policy_document" {
  statement {
    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${local.account_id}:role/api.${local.environment}"]
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
      identifiers = ["arn:aws:iam::${local.account_id}:role/pdf2.${local.environment}"]
    }

    effect    = "Allow"
    resources = [aws_sqs_queue.pdf_fifo_queue.arn]

    actions = [
      "sqs:DeleteMessage",
      "sqs:ReceiveMessage",
    ]
  }
}

