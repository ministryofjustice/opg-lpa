terraform {
  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "moj-lasting-power-of-attorney-environment/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    role_arn       = "arn:aws:iam::311462405659:role/opg-lpa-ci"
    dynamodb_table = "remote_lock"
  }
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
  alias  = "eu_west_1"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"

  }

}
provider "aws" {
  region = "eu-west-2"
  alias  = "eu_west_2"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }

}

provider "aws" {
  alias  = "us_east_1"
  region = "us-east-1"
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
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
}

provider "pagerduty" {
  token = var.pagerduty_token
}


data "aws_region" "current" {}
