terraform {
  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "moj-lasting-power-of-attorney-maintenance/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    role_arn       = "arn:aws:iam::311462405659:role/opg-lpa-ci"
    dynamodb_table = "remote_lock"
  }
}

provider "aws" {
  region = "eu-west-1"
  assume_role {
    role_arn     = "arn:aws:iam::550790013665:role/${var.default_role}"
    session_name = "terraform-session"
  }
  version = "2.70.0"
}

provider "aws" {
  alias  = "us_east_1"
  region = "us-east-1"
  assume_role {
    role_arn     = "arn:aws:iam::550790013665:role/${var.default_role}"
    session_name = "terraform-session"
  }
  version = "2.70.0"
}

provider "aws" {
  alias  = "new_lpa_prod_ecs"
  region = "eu-west-1"
  assume_role {
    role_arn     = "arn:aws:iam::980242665824:role/${var.default_role}"
    session_name = "terraform-session"
  }
  version = "2.70.0"
}

provider "aws" {
  alias  = "eu_central_1"
  region = "eu-central-1"
  assume_role {
    role_arn     = "arn:aws:iam::550790013665:role/${var.default_role}"
    session_name = "terraform-session"
  }
  version = "2.70.0"
}

provider "aws" {
  region = "eu-west-1"
  alias  = "management"
  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
  version = "2.70.0"
}

variable "default_role" {
  default = "opg-lpa-ci"
}

variable "management_role" {
  default = "opg-lpa-ci"
}
