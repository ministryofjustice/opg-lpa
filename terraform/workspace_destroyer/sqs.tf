resource "aws_sqs_queue" "workspace_destroyer" {
  name                       = "${local.account_name}-opg-lpa-workspace-destroyer-queue"
  max_message_size           = 2048
  visibility_timeout_seconds = 900
  tags                       = local.default_tags
}

