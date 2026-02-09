provider "aws" {
  region = "eu-west-1"
  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

variable "default_role" {
  default     = "opg-lpa-ci"
  type        = string
  description = "The role to assume when creating resources"
}

variable "management_role" {
  default     = "opg-lpa-ci"
  type        = string
  description = "The role to assume when creating resources in the management account"
}

# tflint-ignore-file:terraform_unused_declarations
# lines 25- 32 put under tfliint ignore to temporarily disable the rule for unused declarations. TF Lint stage failing in pipeline due to new linting version contraints
# # This will be removed once the providers are moved to a separate module.
provider "aws" {
  region = "eu-west-1"
  alias  = "management"
  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
}
