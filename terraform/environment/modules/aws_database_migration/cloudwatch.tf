resource "aws_cloudwatch_log_group" "dms_tasks" {
  name              = "/aws/dms/tasks/${coalesce(try(var.task.id, null), "aurora-${var.environment_name}-dms-task")}"
  retention_in_days = var.log_retention_days
  kms_key_id        = var.cloudwatch_kms_key_arn

  tags = var.tags
}
