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

provider "aws" {
  region = "eu-west-1"
  alias  = "management"
  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
}
