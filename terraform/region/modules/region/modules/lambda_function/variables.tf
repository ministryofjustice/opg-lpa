
variable "lambda_role_policy_document" {
  description = "The policy JSON for the lambda IAM role. This policy JSON is merged with Logging and ECR access included in the module."
  type        = string
  default     = null
}

variable "command" {
  description = "The CMD for the docker image."
  type        = list(string)
  default     = null
}

variable "entry_point" {
  description = "The ENTRYPOINT for the docker image."
  type        = list(string)
  default     = null
}

variable "environment_variables" {
  description = "A map that defines environment variables for the Lambda Function."
  type        = map(string)
  default     = {}
}

variable "image_uri" {
  description = "The URI for the container image to use"
  type        = string
  default     = null
}
variable "ecr_arn" {
  description = "The ARN for the ECR Repository"
  type        = string
  default     = null
}

variable "lambda_name" {
  description = "A unique name for your Lambda Function"
  type        = string
}

variable "package_type" {
  description = "The Lambda deployment package type."
  type        = string
  default     = "Image"
}

variable "timeout" {
  description = "The amount of time your Lambda Function has to run in seconds."
  type        = number
  default     = 3
}

variable "tags" {
  description = "A map of tags to assign to resources."
  type        = map(string)
  default     = {}
}

variable "working_directory" {
  description = "The working directory for the docker image."
  type        = string
  default     = null
}

variable "log_retention_in_days" {
  description = "log retention period on cloudwatch. defaults to 400 to meet minimum moj security requirements"
  type        = number
  default     = 400

  validation {
    condition     = contains([0, 1, 3, 5, 7, 14, 30, 60, 90, 120, 150, 180, 365, 400, 545, 731, 1827, 3653], var.log_retention_in_days)
    error_message = "Log retention in days is invalid, allowed values are 1, 3, 5, 7, 14, 30, 60, 90, 120, 150, 180, 365, 400, 545, 731, 1827, 3653, and 0."
  }
}
