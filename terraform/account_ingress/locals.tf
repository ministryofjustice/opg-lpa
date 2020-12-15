# variables for terraform.tfvars.json
variable "account_mapping" {
  type = map(any)
}

variable "accounts" {
  type = map(
    object({
      account_id                 = string
      allow_ingress_modification = bool
    })
  )
}

locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account      = var.accounts[local.account_name]
  environment  = terraform.workspace
}
