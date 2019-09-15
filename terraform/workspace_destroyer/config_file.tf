resource "local_file" "workspace_detroyer_config" {
  content  = "${jsonencode(local.workspace_detroyer_config)}"
  filename = "/tmp/workspace_detroyer_config.json"
}

locals {
  workspace_detroyer_config = {
    account_id                  = local.account_id
    workspace_destory_queue_url = aws_sqs_queue.workspace_destroyer.id
  }
}

