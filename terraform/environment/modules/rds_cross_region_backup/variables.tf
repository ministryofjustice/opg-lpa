variable "source_db_instance_arn" {
    type        = string
    description = "The arn for the source database"
}

variable "cross_region_rds_key_arn"{
    type        = string
    description = "the multi region key in use for the backup"
}

variable  "retention_period" {
    type        = number
    description = "retention period of DB in days"
}
