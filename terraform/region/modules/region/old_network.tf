#tfsec:ignore:aws-ec2-no-default-vpc this is currently std practice. will look to change later if needed
resource "aws_default_vpc" "default" {
  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "default"
    },
  )
}


resource "aws_eip" "nat" {
  count = 3

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "nat"
    },
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
  route_table_id = element(aws_route_table.private[*].id, count.index)
  subnet_id      = element(aws_subnet.private[*].id, count.index)
}

resource "aws_nat_gateway" "nat" {
  count         = 3
  allocation_id = element(aws_eip.nat[*].id, count.index)
  subnet_id     = element(aws_default_subnet.public[*].id, count.index)

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "nat"
    },
  )
}

resource "aws_default_route_table" "default" {
  default_route_table_id = aws_default_vpc.default.default_route_table_id

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "default"
    },
  )
}

resource "aws_route_table" "private" {
  count  = 3
  vpc_id = aws_default_vpc.default.id

  tags = merge(
    local.shared_component_tag,
    {
      "Name" = "private"
    },
  )
}

resource "aws_route" "default" {
  route_table_id         = aws_default_route_table.default.id
  destination_cidr_block = "0.0.0.0/0"
  gateway_id             = data.aws_internet_gateway.default.internet_gateway_id
}

resource "aws_route" "private" {
  count                  = 3
  route_table_id         = element(aws_route_table.private[*].id, count.index)
  destination_cidr_block = "0.0.0.0/0"
  nat_gateway_id         = element(aws_nat_gateway.nat[*].id, count.index)
}

resource "aws_flow_log" "vpc_flow_logs" {
  iam_role_arn    = data.aws_iam_role.vpc_flow_logs.arn
  log_destination = aws_cloudwatch_log_group.vpc_flow_logs_region.arn
  traffic_type    = "ALL"
  vpc_id          = aws_default_vpc.default.id
}

#tfsec:ignore:aws-cloudwatch-log-group-customer-key cost to encrypt is expensive. this is legacy so keep for now.
resource "aws_cloudwatch_log_group" "vpc_flow_logs" {
  name = "vpc_flow_logs"
  tags = local.shared_component_tag
}

#tfsec:ignore:aws-cloudwatch-log-group-customer-key cost to encrypt is expensive. region support needed
resource "aws_cloudwatch_log_group" "vpc_flow_logs_region" {
  name = "vpc_flow_logs_${local.account_name}_${local.region_name}"
  tags = local.shared_component_tag
}


# this is held at the account level, so we reference it.
data "aws_iam_role" "vpc_flow_logs" {
  name = "vpc_flow_logs"
}


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
    values = [aws_default_vpc.default.id]
  }
  filter {
    name   = "tag:Name"
    values = ["private"]
  }

  depends_on = [
    aws_subnet.private
  ]
}

locals {
  private_route_tables = {
    ids = aws_route_table.private[*].id
  }
}

module "vpc_endpoints_old_network" {
  source = "./modules/vpc_endpoints"
  interface_endpoint_names = [
    "secretsmanager",
  ]
  vpc_id                          = aws_default_vpc.default.id
  application_subnets_cidr_blocks = aws_subnet.private[*].cidr_block
  application_subnets_id          = aws_subnet.private[*].id
  public_subnets_cidr_blocks      = aws_default_subnet.public[*].cidr_block
  application_route_tables        = local.private_route_tables
  s3_endpoint_enabled             = false
  dynamodb_endpoint_enabled       = false
  providers = {
    aws.region = aws
  }
}
