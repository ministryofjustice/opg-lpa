variable "front_dns_name" {
  type        = string
  description = "front service dns name"
}

variable "front_zone_id" {
  type        = string
  description = "front route 53 zone id"
}

variable "admin_dns_name" {
  type        = string
  description = "admin service dns name"
}

variable "admin_zone_id" {
  type        = string
  description = "admin route 53 zone id"
}

variable "mock_onelogin_dns_name" {
  type        = string
  description = "mock onelogin service dns name"
}

variable "mock_onelogin_zone_id" {
  type        = string
  description = "mock onelogin route 53 zone id"
}

variable "account_name" {
  type        = string
  description = "aws account name"
}

variable "environment_name" {
  type        = string
  description = "environment name"
}
