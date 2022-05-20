variable "source_db_instance_arn" {
  type        = string
  description = "The arn for the source database"
}

variable "retention_period" {
  type        = number
  description = "retention period of DB in days"
}

variable "destination_region_name" {
  type        = string
  description = "destination key name"
}


variable "key_alias" {
  type        = string
  description = "key alias"
}
