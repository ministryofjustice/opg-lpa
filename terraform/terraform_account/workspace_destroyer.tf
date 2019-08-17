resource "aws_sqs_queue" "workspace_destoryer_queue" {
  count            = local.account_name == "development" ? 1 : 0
  name             = "${local.account_name}-opg-lpa-workspace-destroyer-queue"
  max_message_size = 2048
  tags             = local.tags
}

resource "local_file" "queue_config" {
  count    = local.account_name == "development" ? 1 : 0
  content  = "${jsonencode(local.queue_config)}"
  filename = "${path.module}/queue_config.json"
}

locals {
  queue_config = {
    account_id                  = "${local.account_id}"
    workspace_destory_queue_url = "${aws_sqs_queue.workspace_destoryer_queue.id}"
  }
}
