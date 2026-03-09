output "admin_domain" {
  value = module.environment_dns.admin_domain
}

output "front_domain" {
  value = module.environment_dns.front_domain
}

output "front_fqdn" {
  value = module.environment_dns.front_fqdn
}

output "admin_fqdn" {
  value = module.environment_dns.admin_fqdn
}

output "front_sg_id" {
  value = !local.dr_enabled ? module.eu-west-1.front_sg_id : module.eu-west-2[0].front_sg_id
}

output "admin_sg_id" {
  value = !local.dr_enabled ? module.eu-west-1.admin_sg_id : module.eu-west-2[0].admin_sg_id
}
