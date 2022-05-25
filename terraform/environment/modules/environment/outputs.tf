output "front_zone_id" {
  value = aws_lb.front.zone_id
}

output "front_dns_name" {
  value = aws_lb.front.dns_name
}

output "admin_zone_id" {
  value = aws_lb.admin.zone_id
}

output "admin_dns_name" {
  value = aws_lb.admin.dns_name
}

output "db_client_security_group_id" {
  value = aws_security_group.rds-client.id
}

output "seeding_security_group_id" {
  value = aws_security_group.seeding_ecs_service.id
}

output "feedbackdb_security_group_id" {
  value = aws_security_group.feedbackdb_ecs_service.id
}

output "cluster_name" {
  value = aws_ecs_cluster.online-lpa.name
}
