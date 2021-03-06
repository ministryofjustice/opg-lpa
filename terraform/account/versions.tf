terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.0"
    }
    pagerduty = {
      source  = "pagerduty/pagerduty"
      version = "~> 1.7"
    }
  }
  required_version = ">= 0.13"
}
