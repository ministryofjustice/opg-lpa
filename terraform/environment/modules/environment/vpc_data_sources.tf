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


module "allowed_ip_list" {
  source = "github.com/ministryofjustice/terraform-aws-moj-ip-whitelist.git?ref=v2.3.2"
}
