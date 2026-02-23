terraform {
  backend "s3" {
    bucket  = "opg.terraform.state"
    key     = "moj-lasting-power-of-attorney/terraform.tfstate"
    encrypt = true
    region  = "eu-west-1"
    assume_role = {
      role_arn = "arn:aws:iam::311462405659:role/make-a-lasting-power-of-attorney-state-access"
    }
    use_lockfile = true
  }
}

provider "aws" {
  region = "eu-west-1"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "pagerduty" {
  token = var.pagerduty_token
}
