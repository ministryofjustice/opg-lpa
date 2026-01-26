terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = ">= 6.2.0"
      configuration_aliases = [
        aws.eu_west_1,
        aws.eu_west_2,
        aws.backup,
      ]
    }
  }
  required_version = ">= 1.7.3"
}
