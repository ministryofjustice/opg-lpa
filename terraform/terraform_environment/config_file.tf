resource "local_file" "environment_pipeline_tasks_config" {
  content  = jsonencode(local.environment_pipeline_tasks_config)
  filename = "/tmp/environment_pipeline_tasks_config.json"
}

locals {
  front_fqdn = local.account_name == "production" ? "www.lastingpowerofattorney.service.gov.uk" : aws_route53_record.front.fqdn

  environment_pipeline_tasks_config = {
    account_id                  = local.account_id
    cluster_name                = aws_ecs_cluster.online-lpa.name
    environment                 = local.environment
    front_fqdn                  = local.front_fqdn
    admin_fqdn                  = aws_route53_record.admin.fqdn
    tag                         = var.container_version
    db_client_security_group_id = aws_security_group.rds-client.id
    seeding_security_group_id   = aws_security_group.seeding_ecs_service.id
  }
}
