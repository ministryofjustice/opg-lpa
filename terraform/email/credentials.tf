terraform {
  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "moj-lasting-power-of-attorney-email/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    dynamodb_table = "remote_lock"
    assume_role = {
      role_arn = "arn:aws:iam::311462405659:role/opg-lpa-ci"
    }
  }
}

variable "default_role" {
  default     = "opg-lpa-ci"
  type        = string
  description = "The default role to assume"
}

provider "aws" {
  region = "eu-west-1"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "management"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.default_role}"
    session_name = "terraform-session"
  }
}
