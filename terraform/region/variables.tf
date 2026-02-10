variable "default_role" {
  description = "default aws IAM role to use. defaults to the CI Role"
  default     = "opg-lpa-ci"
  type        = string
}

variable "pagerduty_token" {
  description = "pagerduty token"
  type        = string
  sensitive   = true
}

# variables for terraform.tfvars.json
variable "account_mapping" {
  description = "maps the tfvars.json files to accounts"
  type        = map(any)
}

variable "accounts" {
  description = "the account map loaded from tfvars.json"
  type = map(
    object({
      account_id         = string
      account_name_short = string
      is_production      = string
      retention_in_days  = number
      dr_enabled         = bool
      always_on_aurora   = bool
      dns_firewall = object({
        enabled         = bool
        domains_allowed = list(string)
        domains_blocked = list(string)
      })
      old_network_vpc_endpoints_enabled = bool
      firewalled_vpc_cidr_ranges = object({
        eu_west_1 = string
        eu_west_2 = string
      })
      network_firewall_rules = object({
        allowed_domains          = list(string)
        allowed_prefixed_domains = list(string)
      })
      shared_firewall_configuration = object({
        enabled      = bool
        account_id   = string
        account_name = string
      })
      regions = map(
        object({
          region     = string
          is_primary = string
      }))
    })
  )
}
