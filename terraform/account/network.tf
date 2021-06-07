resource "aws_default_vpc" "default" {
  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "default"
    })
  )
}

data "aws_availability_zones" "default" {}

resource "aws_default_subnet" "public" {
  count             = 3
  availability_zone = element(data.aws_availability_zones.default.names, count.index)

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "public"
    })
  )
}

resource "aws_subnet" "private" {
  count      = 3
  cidr_block = cidrsubnet(aws_default_vpc.default.cidr_block, 4, count.index + 3)
  vpc_id     = aws_default_vpc.default.id

  availability_zone = element(data.aws_availability_zones.default.names, count.index)

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "private"
    })
  )
}

resource "aws_eip" "nat" {
  count = 3

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "nat"
    })
  )
}

data "aws_internet_gateway" "default" {
  filter {
    name   = "attachment.vpc-id"
    values = [aws_default_vpc.default.id]
  }
}

resource "aws_route_table_association" "private" {
  count          = 3
  route_table_id = element(aws_route_table.private.*.id, count.index)
  subnet_id      = element(aws_subnet.private.*.id, count.index)
}

resource "aws_nat_gateway" "nat" {
  count         = 3
  allocation_id = element(aws_eip.nat.*.id, count.index)
  subnet_id     = element(aws_default_subnet.public.*.id, count.index)

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "nat"
    })
  )
}

resource "aws_default_route_table" "default" {
  default_route_table_id = aws_default_vpc.default.default_route_table_id

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "default"
    })
  )
}

resource "aws_route_table" "private" {
  count  = 3
  vpc_id = aws_default_vpc.default.id

  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "private"
    })
  )
}

resource "aws_route" "default" {
  route_table_id         = aws_default_route_table.default.id
  destination_cidr_block = "0.0.0.0/0"
  gateway_id             = data.aws_internet_gateway.default.id
}

resource "aws_route" "private" {
  count                  = 3
  route_table_id         = element(aws_route_table.private.*.id, count.index)
  destination_cidr_block = "0.0.0.0/0"
  nat_gateway_id         = element(aws_nat_gateway.nat.*.id, count.index)
}


resource "aws_db_subnet_group" "data_persistence" {
  name       = "data-persistence-subnet-default"
  subnet_ids = data.aws_subnet_ids.data_persistence.ids # todo: create new subnet for data layer.
}

data "aws_subnet_ids" "data_persistence" {
  vpc_id = aws_default_vpc.default.id # todo: create new subnet for data layer.

  filter {
    name   = "tag:Name"
    values = ["private"]
  }
}
