variable "environment_name" {
  type = string
}

variable "db_cluster_identifier" {
  type = string
}

variable "api_rds_credentials_secret_name" {
  type = string
}

variable "vpc_subnet_ids" {
  type = list(string)
}

variable "rds_client_security_group_id" {
  type = string
}

variable "rds_api_security_group_id" {
  type = string
}

variable "vpc_id" {
  type = string
}

variable "secretsmanager_encryption_key_arn" {
  type = string
}
