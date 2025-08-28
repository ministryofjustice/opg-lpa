# old network
data "aws_vpc" "default" {
  default = "true"
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

# firewalled network
data "aws_availability_zones" "available" {
}

data "aws_vpc" "main" {
  filter {
    name   = "tag:application"
    values = [data.aws_default_tags.current.tags.application]
  }
  filter {
    name   = "tag:name"
    values = ["${data.aws_default_tags.current.tags.application}-${data.aws_default_tags.current.tags.account-name}-vpc"]
  }
}

# tflint-ignore: terraform_unused_declarations
data "aws_subnet" "application" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["application*"]
  }
}

# tflint-ignore: terraform_unused_declarations
data "aws_subnet" "public" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["public*"]
  }
}
