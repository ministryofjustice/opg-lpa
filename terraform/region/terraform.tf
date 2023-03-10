terraform {
  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "moj-lasting-power-of-attorney-region/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    role_arn       = "arn:aws:iam::311462405659:role/opg-lpa-ci"
    dynamodb_table = "remote_lock"
  }

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 4.58.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 2.0"
    }
  }
  required_version = ">= 1.1.2"
}

provider "aws" {
  region = "eu-west-1"
  default_tags {
    tags = local.default_opg_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  alias  = "eu-west-1"
  region = "eu-west-1"
  default_tags {
    tags = local.default_opg_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-2"
  alias  = "eu-west-2"
  default_tags {
    tags = local.default_opg_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  alias  = "us_east_1"
  region = "us-east-1"
  default_tags {
    tags = local.default_opg_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "management"
  default_tags {
    tags = local.default_opg_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-2"
  alias  = "management_eu_west_2"
  default_tags {
    tags = local.default_opg_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "pagerduty" {
  token = var.pagerduty_token
}
