module "network" {
  source                         = "github.com/ministryofjustice/opg-terraform-aws-network?ref=v1.2.0"
  cidr                           = "10.164.0.0/16"
  enable_dns_hostnames           = true
  enable_dns_support             = true
  default_security_group_ingress = []
  default_security_group_egress  = []
}
