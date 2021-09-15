terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.0"
    }
    pagerduty = {
      source  = "pagerduty/pagerduty"
      version = "~> 1.10"
    }
  }
  required_version = ">= 1.0"
}
