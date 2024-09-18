terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 4.67.0"
      configuration_aliases = [
        aws.management
      ]
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 2.0"
    }
  }
  required_version = "1.9.6"
}
