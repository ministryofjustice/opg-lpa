module "allowed_ip_list" {
  source = "git@github.com:ministryofjustice/opg-terraform-aws-moj-ip-allow-list.git?ref=v3.4.5"
}

# Firewalled Network VPC Data Sources
locals {
  application-name = replace(data.aws_default_tags.current.tags.application, " ", "")
  name-prefix      = "${local.application-name}-${var.account_name}"
}

data "aws_vpc" "main" {
  filter {
    name   = "tag:Name"
    values = ["${local.name-prefix}-vpc"]
  }
}

data "aws_availability_zones" "available" {
}

data "aws_subnet" "lb" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["public*"]
  }
}

#tflint-ignore: terraform_unused_declarations
data "aws_subnet" "nat" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["nat*"]
  }
}

data "aws_subnet" "application" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["application*"]
  }
}

data "aws_subnet" "data" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["data*"]
  }
}

data "aws_nat_gateways" "main" {
  vpc_id = data.aws_vpc.main.id

  filter {
    name   = "state"
    values = ["available"]
  }
}

#tflint-ignore: terraform_unused_declarations
data "aws_nat_gateway" "main" {
  count = length(data.aws_nat_gateways.main.ids)
  id    = tolist(data.aws_nat_gateways.main.ids)[count.index]
}
