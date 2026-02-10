variable "account" {
  description = "the account object passed into the region module."
  type = object({
    account_name_short = string
    account_id         = string
    is_production      = string
    dr_enabled         = bool
    always_on_aurora   = bool
    retention_in_days  = number
    regions = map(
      object({
        region     = string
        is_primary = string
    }))
    dns_firewall = object({
      enabled         = bool
      domains_allowed = list(string)
      domains_blocked = list(string)
    })
    old_network_vpc_endpoints_enabled = bool
    network_firewall_rules = object({
      enabled                  = bool
      allowed_domains          = list(string)
      allowed_prefixed_domains = list(string)
    })
    shared_firewall_configuration = object({
      enabled      = bool
      account_id   = string
      account_name = string
    })
  })
}

variable "account_name" {
  description = "account name passed into the region module"
  type        = string
}

variable "firewalled_vpc_cidr_range" {
  type        = string
  description = "The IPv4 CIDR block for the VPC. CIDR can be explicitly set or it can be derived from IPAM using ipv4_netmask_length."
}
