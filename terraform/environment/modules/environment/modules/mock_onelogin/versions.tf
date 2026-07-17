terraform {
  required_version = ">= 1.5.2"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = ">= 6.28.0"
      configuration_aliases = [
        aws.region,
        aws.management,
      ]
    }
  }
}
