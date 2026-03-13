output "dms_replication_instance_arn" {
  description = "ARN of the DMS replication instance."
  value       = aws_dms_replication_instance.aurora_migration.replication_instance_arn
}

output "dms_replication_subnet_group_id" {
  description = "ID of the DMS replication subnet group."
  value       = aws_dms_replication_subnet_group.aurora_migration.id
}

output "dms_replication_security_group_id" {
  description = "ID of the security group attached to the DMS replication instance."
  value       = aws_security_group.aurora_migration_replication.id
}

output "dms_source_endpoint_arn" {
  description = "ARN of the DMS source endpoint."
  value       = aws_dms_endpoint.source.endpoint_arn
}

output "dms_target_endpoint_arn" {
  description = "ARN of the DMS target endpoint."
  value       = aws_dms_endpoint.target.endpoint_arn
}

output "dms_replication_task_arn" {
  description = "ARN of the DMS replication task."
  value       = aws_dms_replication_task.aurora_migration.replication_task_arn
}

output "dms_vpc_role_arn" {
  description = "ARN of the DMS VPC management role if created."
  value       = var.create_iam_roles ? aws_iam_role.dms_vpc_role[0].arn : null
}

output "dms_cloudwatch_role_arn" {
  description = "ARN of the DMS CloudWatch logs role if created."
  value       = var.create_iam_roles ? aws_iam_role.dms_cloudwatch_role[0].arn : null
}
