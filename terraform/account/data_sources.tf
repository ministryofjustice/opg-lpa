data "aws_subnet_ids" "private" {
  vpc_id = data.aws_vpc.default.id

  tags = {
    Name = "private*"
  }
}

data "aws_vpc" "default" {
  tags = {
    Name = "default"
  }
}
