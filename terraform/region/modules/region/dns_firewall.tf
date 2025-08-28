resource "aws_cloudwatch_log_group" "aws_route53_resolver_query_log" {
  count             = var.account.dns_firewall.enabled ? 1 : 0
  name              = "${var.account_name}-${data.aws_region.current.region}-make-an-lpa-aws-route53-resolver-query-log-config"
  retention_in_days = 400
  kms_key_id        = aws_kms_key.cloudwatch_encryption.arn
  tags = merge(
    local.shared_component_tag, {
      "Name" = "make-an-lpa-aws-route53-resolver-query-log-config"
    },
  )
}

resource "aws_route53_resolver_query_log_config" "egress" {
  count           = var.account.dns_firewall.enabled ? 1 : 0
  name            = "egress"
  destination_arn = aws_cloudwatch_log_group.aws_route53_resolver_query_log[0].arn
}

resource "aws_route53_resolver_query_log_config_association" "egress" {
  count                        = var.account.dns_firewall.enabled ? 1 : 0
  resolver_query_log_config_id = aws_route53_resolver_query_log_config.egress[0].id
  resource_id                  = aws_default_vpc.default.id
}


locals {
  interpolated_dns = [
    "logs.${data.aws_region.current.region}.amazonaws.com.",
    "logs.${data.aws_region.current.region}.amazonaws.com.${data.aws_region.current.region}.compute.internal.",
    "api.ecr.${data.aws_region.current.region}.amazonaws.com.",
    "api.ecr.${data.aws_region.current.region}.amazonaws.com.${data.aws_region.current.region}.compute.internal.",
    "dynamodb.${data.aws_region.current.region}.amazonaws.com.",
    "kms.${data.aws_region.current.region}.amazonaws.com.",
    "secretsmanager.${data.aws_region.current.region}.amazonaws.com.",
    "secretsmanager.${data.aws_region.current.region}.amazonaws.com.${data.aws_region.current.region}.compute.internal.",
    "${replace(aws_elasticache_replication_group.front_cache.primary_endpoint_address, "master", "*")}.",
    "311462405659.dkr.ecr.eu-west-1.amazonaws.com.",
    "${var.account_name}.opg.lpa.api.ecs.internal.",
    "api.${var.account_name}-internal."
  ]
}
resource "aws_route53_resolver_firewall_domain_list" "egress_allow" {
  count   = var.account.dns_firewall.enabled ? 1 : 0
  name    = "egress_allowed_${var.account_name}"
  domains = concat(local.interpolated_dns, var.account.dns_firewall.domains_allowed)
}

resource "aws_route53_resolver_firewall_domain_list" "egress_block" {
  count   = var.account.dns_firewall.enabled ? 1 : 0
  name    = "egress_blocked_${var.account_name}"
  domains = var.account.dns_firewall.domains_blocked
}

resource "aws_route53_resolver_firewall_rule_group" "egress" {
  count = var.account.dns_firewall.enabled ? 1 : 0
  name  = "egress_${var.account_name}"
}

resource "aws_route53_resolver_firewall_rule" "egress_allow" {
  count                   = var.account.dns_firewall.enabled ? 1 : 0
  name                    = "egress_allowed_${var.account_name}"
  action                  = "ALLOW"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_allow[0].id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority                = 100
}

resource "aws_route53_resolver_firewall_rule" "egress_block" {
  count  = var.account.dns_firewall.enabled ? 1 : 0
  name   = "egress_blocked_${var.account_name}"
  action = "ALERT"
  # action                  = "BLOCK"
  # block_response          = "NODATA"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_block[0].id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority                = 200
}

resource "aws_route53_resolver_firewall_rule_group_association" "egress" {
  count                  = var.account.dns_firewall.enabled ? 1 : 0
  name                   = "egress_${var.account_name}"
  firewall_rule_group_id = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority               = 300
  vpc_id                 = aws_default_vpc.default.id
}


resource "aws_cloudwatch_query_definition" "dns_firewall_statistics" {
  count = var.account.dns_firewall.enabled ? 1 : 0
  name  = "${var.account_name} ${data.aws_region.current.region} DNS Firewall Queries/DNS Firewall Statistics"

  log_group_names = [aws_cloudwatch_log_group.aws_route53_resolver_query_log[0].name]

  query_string = <<EOF
fields @timestamp, query_name, firewall_rule_action
| sort @timestamp desc
| stats count() as frequency by query_name, firewall_rule_action
EOF
}
