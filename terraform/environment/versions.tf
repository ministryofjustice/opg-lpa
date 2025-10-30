terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "6.9.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 3.0"
    }
  }

  required_version = "1.12.2"
}
