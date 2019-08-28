# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
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

  account                    = var.accounts[local.account_name]
  account_id                 = local.account.account_id
  allow_ingress_modification = local.account.allow_ingress_modification
  environment                = terraform.workspace
}
