terraform {
  required_version = "~> 1.13.0"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
      ]
      version = "6.21.0"
    }
  }
}
