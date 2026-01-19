terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "6.28.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "2.6.1"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "3.30.9"
    }
  }

  required_version = "1.14.3"
}
