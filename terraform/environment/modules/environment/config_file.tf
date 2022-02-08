resource "local_file" "environment_pipeline_tasks_config" {
  content  = jsonencode(local.environment_pipeline_tasks_config)
  filename = "/tmp/environment_pipeline_tasks_config.json"
}

locals {

  environment_pipeline_tasks_config = {
    account_id                     = var.account.account_id
    cluster_name                   = aws_ecs_cluster.online-lpa.name
    environment                    = var.environment_name
    front_fqdn                     = aws_route53_record.front.fqdn
    admin_fqdn                     = aws_route53_record.admin.fqdn
    public_facing_fqdn             = aws_route53_record.public_facing_lastingpowerofattorney.fqdn
    tag                            = var.container_version
    db_client_security_group_id    = aws_security_group.rds-client.id
    seeding_security_group_id      = aws_security_group.seeding_ecs_service.id
    feedbackdb_security_group_id   = aws_security_group.feedbackdb_ecs_service.id
  }
}
