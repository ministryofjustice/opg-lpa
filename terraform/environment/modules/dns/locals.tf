
locals {
  cert_prefix_public_facing = var.environment_name == "production" ? "www." : "*."
  cert_prefix_internal      = var.account_name == "production" ? "" : "*."
  dns_namespace_env         = var.environment_name == "production" ? "" : "${var.environment_name}."
  dns_namespace_env_public  = var.environment_name == "production" ? "www." : "${var.environment_name}."
  dns_namespace_dev_prefix  = var.account_name == "development" ? "development." : ""
  front_dns                 = "front.lpa"
  admin_dns                 = "admin.lpa"
}
