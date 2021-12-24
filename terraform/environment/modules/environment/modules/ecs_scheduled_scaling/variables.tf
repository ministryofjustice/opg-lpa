variable "ecs_cluster_name" {
  description = "Name of the ECS Cluster"
  type        = string
}

variable "name" {
  description = "Schedule name if running multiple schedules"
  type        = string
  default     = "hibernation"
}

variable "scale_down_time" {
  description = "Cron formatted value for scale down trigger"
}

variable "scale_up_time" {
  description = "Cron formatted value for scale up trigger"
}

variable "service_config" {
  description = "Map of services and task scale down to and up to when regually scaled."
  type = map(
    object({
      scale_down_to = number
      scale_up_to   = number
      target        = map(any)
  }))
}
