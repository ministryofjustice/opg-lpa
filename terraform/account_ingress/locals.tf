# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
}

variable "accounts" {
  type = map(
    object({
      account_id = string
    })
  )
}

locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account_id   = var.accounts[local.account_name].account_id
  environment  = terraform.workspace
}
