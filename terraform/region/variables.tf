variable "default_role" {
  default = "opg-lpa-ci"
}

variable "pagerduty_token" {
}

# variables for terraform.tfvars.json
variable "account_mapping" {
  type = map(any)
}

variable "accounts" {
  type = map(
    object({
      account_id         = string
      account_name_short = string
      is_production      = string
      retention_in_days  = number
      dr_enabled         = bool
      regions = map(
        object({
          region     = string
          is_primary = string
      }))
    })
  )
}
