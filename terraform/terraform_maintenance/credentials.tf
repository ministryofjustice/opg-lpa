terraform {
  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "moj-lasting-power-of-attorney-maintenance/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    role_arn       = "arn:aws:iam::311462405659:role/state_write"
    dynamodb_table = "remote_lock"
  }
}

provider "aws" {
  region = "eu-west-1"

  assume_role {
    role_arn     = "arn:aws:iam::550790013665:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  alias  = "us_east_1"
  region = "us-east-1"

  assume_role {
    role_arn     = "arn:aws:iam::550790013665:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  alias  = "eu_central_1"
  region = "eu-central-1"

  assume_role {
    role_arn     = "arn:aws:iam::550790013665:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "management"

  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
}

variable "default_role" {
  default = "ci"
}

variable "management_role" {
  default = "ci"
}

