module "network" {
  source                              = "github.com/ministryofjustice/opg-terraform-aws-firewalled-network?ref=v1.1.0"
  aws_networkfirewall_firewall_policy = aws_networkfirewall_firewall_policy.main
  cidr                                = var.firewalled_vpc_cidr_range
  default_security_group_egress       = []
  default_security_group_ingress      = []
  enable_dns_hostnames                = true
  enable_dns_support                  = true
  providers = {
  aws = aws }
}

resource "aws_networkfirewall_firewall_policy" "main" {
  name = "main"

  firewall_policy {
    stateless_default_actions          = ["aws:forward_to_sfe"]
    stateless_fragment_default_actions = ["aws:forward_to_sfe"]

    stateful_engine_options {
      rule_order              = "DEFAULT_ACTION_ORDER"
      stream_exception_policy = "DROP"
    }
    stateful_rule_group_reference {
      resource_arn = aws_networkfirewall_rule_group.rule_file.arn
    }
  }
}

resource "aws_networkfirewall_rule_group" "rule_file" {
  capacity = 100
  name     = "main-${replace(filebase64sha256("${path.module}/network_firewall_rules_${data.aws_default_tags.current.tags.environment-name}.rules"), "/[^[:alnum:]]/", "")}"
  type     = "STATEFUL"
  rules    = file("${path.module}/network_firewall_rules_${data.aws_default_tags.current.tags.environment-name}.rules")
  lifecycle {
    create_before_destroy = true
  }
}

data "aws_route_tables" "firewalled_network_application" {
  filter {
    name   = "tag:Name"
    values = ["application-route-table"]
  }
  filter {
    name   = "vpc-id"
    values = [module.network.vpc.id]
  }
}

module "vpc_endpoints" {
  source = "./modules/vpc_endpoints"
  interface_endpoint_names = [
    "ec2",
    "ecr.api",
    "ecr.dkr",
    "execute-api",
    "events",
    "logs",
    "rum",
    "secretsmanager",
    "ssm",
    "xray",
    "kms",
    "monitoring",
  ]
  vpc_id                          = module.network.vpc.id
  application_subnets_cidr_blocks = module.network.application_subnets[*].cidr_block
  application_subnets_id          = module.network.application_subnets[*].id
  public_subnets_cidr_blocks      = module.network.public_subnets[*].cidr_block
  application_route_tables        = data.aws_route_tables.firewalled_network_application
  providers = {
    aws.region = aws
  }
}

resource "aws_db_subnet_group" "data" {
  name       = "data"
  subnet_ids = module.network.data_subnets[*].id
}
