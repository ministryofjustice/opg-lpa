variable "interface_endpoint_names" {
  description = "a list of the interfaces to create VPC endpoints for"
  type        = list(string)
}

variable "vpc_id" {
  description = "id of the VPC to create interface endpoints in"
  type        = string
}

variable "application_subnets_cidr_blocks" {
  description = "application subnet CIDR blocks"
  type        = any
}

variable "application_subnets_id" {
  description = "application subnet CIDR blocks"
  type        = any
}

variable "public_subnets_cidr_blocks" {
  description = "public subnet CIDR blocks"
  type        = any
}

variable "application_route_tables" {
  type = any
}
