variable "account" {
  description = "the account object passed into the region module."
  type = object({
    account_name_short = string
    account_id         = string
    is_production      = string
    dr_enabled         = bool
    retention_in_days  = number
    regions = map(
      object({
        region     = string
        is_primary = string
    }))
  })
}

variable "account_name" {
  description = "account name passed into the region module"
  type        = string
}
