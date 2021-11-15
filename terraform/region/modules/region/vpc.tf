#tfsec:ignore:AWS082 this is currently std practice. will look to change later if needed
resource "aws_default_vpc" "default" {
  tags = merge(
    local.default_tags,
    local.shared_component_tag,
    tomap({
      "Name" = "default"
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

resource "aws_internet_gateway" "default" {
  vpc_id = aws_default_vpc.default.id
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
  gateway_id             = aws_internet_gateway.default.id
}

resource "aws_route" "private" {
  count                  = 3
  route_table_id         = element(aws_route_table.private.*.id, count.index)
  destination_cidr_block = "0.0.0.0/0"
  nat_gateway_id         = element(aws_nat_gateway.nat.*.id, count.index)
}
