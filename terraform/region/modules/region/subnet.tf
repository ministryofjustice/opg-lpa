
resource "aws_default_subnet" "public" {
  count                   = 3
  availability_zone       = element(data.aws_availability_zones.default.names, count.index)
  map_public_ip_on_launch = false
  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "public"
    },
  )
}

resource "aws_subnet" "private" {
  count      = 3
  cidr_block = cidrsubnet(aws_default_vpc.default.cidr_block, 4, count.index + 3)
  vpc_id     = aws_default_vpc.default.id

  availability_zone = element(data.aws_availability_zones.default.names, count.index)

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "private"
    },
  )
}

resource "aws_db_subnet_group" "data_persistence" {
  name       = "data-persistence-subnet-default"
  subnet_ids = data.aws_subnets.data_persistence.ids # todo: create new subnet for data layer.
}

data "aws_subnets" "data_persistence" {
  filter {
    name   = "vpc-id"
    values = [aws_default_vpc.default.id] # todo: create new subnet for data layer.
  }
  filter {
    name   = "tag:Name"
    values = ["private"]
  }

  depends_on = [
    aws_subnet.private
  ]
}
