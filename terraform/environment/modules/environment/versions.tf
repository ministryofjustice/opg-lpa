terraform {
  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.management
      ]
      version = "~> 5.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 2.0"
    }
  }
  required_version = "1.12.2"
}
