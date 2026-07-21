variable "default_role" {
  default     = "opg-lpa-ci"
  description = "The default role to assume when running terraform"
  type        = string
}

variable "pagerduty_token" {
  type        = string
  description = "The API token of the PagerDuty service"
}

# variables for terraform.tfvars.json
variable "account_mapping" {
  type        = map(any)
  description = "A map of account names to account IDs"
}

variable "accounts" {
  type = map(
    object({
      account_id        = string
      is_production     = string
      retention_in_days = number
    })
  )
  description = "A map of account IDs to account details"
}
