
locals {
  dns_namespace_env        = var.environment_name == "production" ? "" : "${var.environment_name}."
  dns_namespace_env_public = var.environment_name == "production" ? "www." : "${var.environment_name}."
  dns_namespace_dev_prefix = var.account_name == "development" ? "development." : ""
  front_dns                = "front.lpa"
  admin_dns                = "admin.lpa"
}
