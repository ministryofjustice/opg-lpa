locals {
  vpc_id               = var.account.firewalled_networks_enabled ? data.aws_vpc.main.id : data.aws_vpc.default.id                                         #tflint-ignore: terraform_unused_declarations
  lb_subnet_ids        = var.account.firewalled_networks_enabled ? [for subnet in data.aws_subnet.lb : subnet.id] : data.aws_subnets.public.ids           #tflint-ignore: terraform_unused_declarations
  app_subnet_ids       = var.account.firewalled_networks_enabled ? [for subnet in data.aws_subnet.application : subnet.id] : data.aws_subnets.private.ids #tflint-ignore: terraform_unused_declarations
  data_subnet_ids      = var.account.firewalled_networks_enabled ? [for subnet in data.aws_subnet.data : subnet.id] : data.aws_subnets.private.ids        #tflint-ignore: terraform_unused_declarations
  db_subnet_group_name = var.account.firewalled_networks_enabled ? aws_db_subnet_group.main.name : "data-persistence-subnet-default"                      #tflint-ignore: terraform_unused_declarations
}


# Old Network VPC Data Sources
data "aws_vpc" "default" {
  default = true
}

data "aws_subnets" "private" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }
  tags = {
    Name = "private*"
  }
}

data "aws_subnets" "public" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }
  tags = {
    Name = "public*"
  }
}

# TODO: Move this out to the root module and call it once. Pass allowlist in as a variable.
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
