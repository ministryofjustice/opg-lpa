terraform {
  required_providers {
    aws = {
      source                = "hashicorp/aws"
      configuration_aliases = [aws.eu_west_1]
      version               = "6.28.0"
    }
  }
  required_version = "1.14.3"
}
