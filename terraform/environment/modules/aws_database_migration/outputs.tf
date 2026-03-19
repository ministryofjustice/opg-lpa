output "replication_instance_arn" {
  description = "ARN of the DMS replication instance."
  value       = aws_dms_replication_instance.aurora_migration.replication_instance_arn
}

output "replication_subnet_group_id" {
  description = "ID of the DMS replication subnet group."
  value       = aws_dms_replication_subnet_group.aurora_migration.id
}

output "replication_security_group_id" {
  description = "ID of the security group for the replication instance."
  value       = aws_security_group.aurora_migration_replication.id
}

output "source_endpoint_arn" {
  description = "ARN of the source database endpoint."
  value       = aws_dms_endpoint.source.endpoint_arn
}

output "target_endpoint_arn" {
  description = "ARN of the target database endpoint."
  value       = aws_dms_endpoint.target.endpoint_arn
}

output "replication_task_arn" {
  description = "ARN of the DMS replication task."
  value       = aws_dms_replication_task.aurora_migration.replication_task_arn
}

output "vpc_role_arn" {
  description = "ARN of the DMS VPC management role (null if not created)."
  value       = var.create_iam_roles ? aws_iam_role.dms_vpc_role[0].arn : null
}

output "cloudwatch_role_arn" {
  description = "ARN of the DMS CloudWatch logs role (null if not created)."
  value       = var.create_iam_roles ? aws_iam_role.dms_cloudwatch_role[0].arn : null
}

output "task_log_group_name" {
  description = "Name of the CloudWatch log group for DMS task logs."
  value       = aws_cloudwatch_log_group.dms_tasks.name
}
