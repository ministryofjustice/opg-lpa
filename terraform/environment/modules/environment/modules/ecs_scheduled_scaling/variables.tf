
variable "name" {
  description = "Schedule name if running multiple schedules"
  type        = string
  default     = "hibernation"
}

variable "scale_down_time" {
  description = "Cron formatted value for scale down trigger"
  type        = string
}

variable "scale_up_time" {
  description = "Cron formatted value for scale up trigger"
  type        = string
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
