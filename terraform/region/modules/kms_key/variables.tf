variable "administrator_roles" {
  description = "List of Role ARNs allowed to administer the KMS Key"
  type        = list(string)
}

variable "alias" {
  description = "KMS Key Alias"
  type        = string
}

variable "custom_addition_permissions" {
  description = "JSON BLOB of Additional Custom Permisisons to be merged with the main key policy."
  type        = string
  default     = ""
}

variable "decryption_roles" {
  description = "List of Role ARNs allowed to use the KMS Key for Decryption"
  type        = list(string)
}

variable "description" {
  description = "KMS Key Description"
  type        = string
}

variable "encryption_roles" {
  description = "List of Role ARNs allowed to use the KMS Key for Encryption"
  type        = list(string)
}

variable "usage_services" {
  description = "List of AWS Service that the usage roles can use the KMS "
  default     = []
  type        = list(string)
}

variable "deletion_window" {
  description = "KMS Key deletion window"
  type        = number
  default     = 7
}
