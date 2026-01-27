variable "account_id" {
  type        = string
  description = "AWS Account ID"
}
variable "apply_immediately" {
  type        = bool
  description = "Specifies whether any cluster modifications are applied immediately, or during the next maintenance window"
}
variable "aurora_serverless" {
  default     = false
  description = "Specifies whether the DB cluster is a serverless cluster"
  type        = bool
}
variable "auto_minor_version_upgrade" {
  default     = false
  description = "Specifies whether minor engine upgrades are applied automatically to the DB instance during the maintenance window"
  type        = bool
}
variable "availability_zones" {
  default     = ["eu-west-1a", "eu-west-1b", "eu-west-1c"]
  description = "A list of Availability Zones (AZs) in which DB instance can be created"
  type        = list(string)
}
variable "backup_retention_period" {
  default     = 14
  description = "The number of days for which automated backups are retained"
  type        = number
}
variable "cluster_identifier" {
  description = "The name of the DB cluster"
  type        = string

}
variable "deletion_protection" {
  description = "Specifies whether the DB cluster is encrypted"
  type        = bool
}

variable "db_subnet_group_name" {
  description = "The name of the DB subnet group to associate with this DB instance"
  type        = string
}
variable "engine" {
  default     = "aurora-postgresql"
  description = "The name of the database engine to be used for this DB instance"
  type        = string
}
variable "engine_version" {
  description = "The version number of the database engine to use"
  type        = string
}
variable "environment" {
  description = "The environment name"
  type        = string

}
variable "kms_key_id" {
  description = "The ARN for the KMS encryption key if one is used to encrypt the DB cluster"
  type        = string

}
variable "master_username" {
  description = "The master username for the DB instance"
  type        = string
}
variable "master_password" {
  description = "The master password for the DB instance"
  type        = string

}
variable "instance_count" {
  default     = 1
  description = "The number of instances to create"
  type        = number
}
variable "instance_class" {
  default     = "db.t3.medium"
  description = "The instance type of the DB instance"
  type        = string
}
variable "publicly_accessible" {
  default     = false
  description = "Specifies the accessibility options for the DB instance"
  type        = bool
}
variable "tags" {
  description = "default resource tags"
  type        = map(string)
}
variable "timeout_create" {
  default     = "50m"
  type        = string
  description = "How long to wait for the RDS Cluster to be created before timing out"
}
variable "timeout_update" {
  default     = "50m"
  type        = string
  description = "How long to wait for the RDS Cluster to be updated before timing out"
}
variable "timeout_delete" {
  default     = "50m"
  type        = string
  description = "How long to wait for the RDS Cluster to be deleted before timing out"
}
variable "skip_final_snapshot" {
  default     = true
  type        = bool
  description = "Determines whether a final DB snapshot is created before the DB cluster is deleted"
}

variable "storage_encrypted" {
  default     = true
  description = "Specifies whether the DB cluster is encrypted"
  type        = bool
}
variable "vpc_security_group_ids" {
  default     = []
  description = "A list of EC2 VPC security groups to associate with this DB instance"
  type        = list(string)

}
variable "replication_source_identifier" {
  default     = ""
  description = "Specifies whether the DB cluster is encrypted"
  type        = string

}
variable "copy_tags_to_snapshot" {
  default     = true
  description = "Specifies whether tags are copied from the DB cluster to snapshots of the DB cluster"
  type        = bool
}
variable "iam_database_authentication_enabled" {
  default     = true
  description = "Specifies whether IAM Database authentication is enabled"
  type        = bool
}

variable "aws_rds_cluster_parameter_group" {
  description = "Information about an RDS cluster parameter group."
  type        = string
}

variable "ca_cert_identifier" {
  default     = "rds-ca-rsa2048-g1"
  description = "Specifies the identifier of the CA certificate for the DB instance"
  type        = string
}

variable "firewalled_networks_enabled" {
  type        = bool
  description = "temporary variable to manage network migration"
}
