terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.0"
    }
    pagerduty = {
      source  = "pagerduty/pagerduty"
      version = "~> 1.7"
    }
  }

  required_version = ">= 1.0"
}
