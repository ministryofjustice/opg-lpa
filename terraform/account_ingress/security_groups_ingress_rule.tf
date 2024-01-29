output "public_ip" {
  value = local.local_ip_cidr
}

locals {
  local_ip_cidr = "${chomp(var.docker_remote_ip)}/32"
}

variable "docker_remote_ip" {
  description = "IP address for CircleCI docker remote"
  type        = string
  default     = ""
}

data "aws_security_group" "admin_loadbalancer" {
  name = "${local.environment}-admin-loadbalancer"
}

resource "aws_security_group_rule" "admin_ci_ingress" {
  count             = local.account.allow_ingress_modification ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = [local.local_ip_cidr]
  security_group_id = data.aws_security_group.admin_loadbalancer.id
  description       = "ci_ingress"
}

data "aws_security_group" "front_loadbalancer" {
  name = "${local.environment}-front-loadbalancer"
}

resource "aws_security_group_rule" "front_ci_ingress" {
  count             = local.account.allow_ingress_modification ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = [local.local_ip_cidr]
  security_group_id = data.aws_security_group.front_loadbalancer.id
  description       = "ci_ingress"
}
