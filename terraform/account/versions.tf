terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "6.27.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "3.30.8"
    }
  }
  required_version = "1.14.3"
}
