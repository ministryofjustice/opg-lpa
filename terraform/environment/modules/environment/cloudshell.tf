resource "aws_security_group" "cloudshell" {
  name_prefix = "${terraform.workspace}-cloudshell"
  description = "Security group for Cloudshell"
  vpc_id      = data.aws_vpc.main.id
  lifecycle {
    create_before_destroy = true
  }
}

# resource "aws_security_group_rule" "cloudshell_egress" {
#   type      = "egress"
#   from_port = 5432 #TODO: can we restrict this to postgres 5432
#   to_port   = 5432 #TODO: can we restrict this to postgres 5432
#   protocol  = "-1"
#   #tfsec:ignore:aws-ec2-no-public-egress-sgr - anything out
#   cidr_blocks       = ["0.0.0.0/0"] #TODO: can we restrict this to postgres
#   security_group_id = aws_security_group.api_ecs_service.id
#   description       = "Cloudshell open egress"
# }
