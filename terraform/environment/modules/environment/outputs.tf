output "front_zone_id" {
  value = aws_lb.front.zone_id
}

output "front_dns_name" {
  value = aws_lb.front.dns_name
}

output "front_sg_id" {
  value = aws_security_group.front_loadbalancer.id
}

output "admin_zone_id" {
  value = aws_lb.admin.zone_id
}

output "admin_dns_name" {
  value = aws_lb.admin.dns_name
}

output "admin_sg_id" {
  value = aws_security_group.admin_loadbalancer.id
}


output "db_client_security_group_id" {
  value = aws_security_group.rds-client.id
}

output "seeding_security_group_id" {
  value = aws_security_group.seeding_ecs_service.id
}

output "cluster_name" {
  value = aws_ecs_cluster.online-lpa.name
}

output "aws_aurora_cluster_arn" {
  value = module.api_aurora[0].cluster.arn
}

output "aws_ecs_task_definition_api_arn" {
  value = aws_ecs_task_definition.api.arn
}
