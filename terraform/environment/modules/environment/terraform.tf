terraform {
  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.management
      ]
    }
    pagerduty = {
      source  = "pagerduty/pagerduty"
      version = "~> 2.0"
    }
  }
  required_version = ">= 1.0.0"
}
