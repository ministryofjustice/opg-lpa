variable "account" {
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
  type = string
}
