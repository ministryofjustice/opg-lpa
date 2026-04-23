resource "aws_security_group" "cloudshell" {
  name_prefix = "${terraform.workspace}-cloudshell"
  description = "Security group for Cloudshell"
  vpc_id      = data.aws_vpc.main.id
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "cloudshell_egress" {
  type                     = "egress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds_api.id
  security_group_id        = aws_security_group.cloudshell.id
  description              = "Cloudshell to Postgres"
}

resource "aws_security_group_rule" "vpc_endpont_egress" {
  type                     = "egress"
  from_port                = 0
  to_port                  = 65536
  protocol                 = "all"
  source_security_group_id = data.aws_security_group.vpc_endpoint.id
  security_group_id        = aws_security_group.cloudshell.id
  description              = "Cloudshell to VPC Endpoint"
}
