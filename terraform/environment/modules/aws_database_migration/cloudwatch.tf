resource "aws_cloudwatch_log_group" "dms_tasks" {
  name              = "/aws/dms/tasks/${local.replication_task_id}"
  retention_in_days = var.log_retention_days
  kms_key_id        = var.cloudwatch_kms_key_arn
  tags              = local.common_tags
}
