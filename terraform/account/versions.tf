terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.0"
    }
    pagerduty = {
      source  = "pagerduty/pagerduty"
      version = "~> 2.0"
    }
  }
  required_version = ">= 1.1.2"
}
