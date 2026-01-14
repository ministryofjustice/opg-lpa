resource "local_file" "environment_pipeline_tasks_config" {
  content  = jsonencode(local.environment_pipeline_tasks_config)
  filename = "/tmp/terraform_artifacts/environment_pipeline_tasks_config.json"
}

locals {
  environment_pipeline_tasks_config = {
    account_id                            = local.account.account_id
    region                                = !local.dr_enabled ? "eu-west-1" : "eu-west-2"
    environment                           = local.environment_name
    front_fqdn                            = module.environment_dns.front_fqdn
    admin_fqdn                            = module.environment_dns.admin_fqdn
    public_facing_fqdn                    = module.environment_dns.public_facing_lastingpowerofattorney_fqdn
    tag                                   = var.container_version
    db_client_security_group_id           = !local.dr_enabled ? module.eu-west-1.db_client_security_group_id : module.eu-west-2[0].db_client_security_group_id
    seeding_security_group_id             = !local.dr_enabled ? module.eu-west-1.seeding_security_group_id : module.eu-west-2[0].seeding_security_group_id
    cluster_name                          = !local.dr_enabled ? module.eu-west-1.cluster_name : module.eu-west-2[0].cluster_name
    front_load_balancer_security_group_id = !local.dr_enabled ? module.eu-west-1.front_sg_id : module.eu-west-2[0].front_sg_id
    admin_load_balancer_security_group_id = !local.dr_enabled ? module.eu-west-1.admin_sg_id : module.eu-west-2[0].admin_sg_id
    vpc_id                                = !local.dr_enabled ? module.eu-west-1.vpc_id : module.eu-west-2[0].vpc_id
  }
}
