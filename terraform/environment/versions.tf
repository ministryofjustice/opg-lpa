terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.14.1"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.0.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 1.7.11"
    }
  }
  required_version = ">= 0.13"
}
