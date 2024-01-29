module "dev_weekdays" {
  count           = var.account_name == "development" ? 1 : 0
  source          = "./modules/ecs_scheduled_scaling"
  name            = "daytime"
  scale_down_time = "cron(00 23 ? * MON-FRI *)"
  scale_up_time   = "cron(30 06 ? * MON-FRI *)"
  service_config = {
    tostring(aws_ecs_service.admin.name) = {
      scale_down_to = 0
      scale_up_to   = var.account.autoscaling.admin.maximum
      target        = module.admin_ecs_autoscaling.appautoscaling_target
    }
    tostring(aws_ecs_service.api.name) = {
      scale_down_to = 0
      scale_up_to   = var.account.autoscaling.api.maximum
      target        = module.api_ecs_autoscaling.appautoscaling_target
    }
    tostring(aws_ecs_service.front.name) = {
      scale_down_to = 0
      scale_up_to   = var.account.autoscaling.front.maximum
      target        = module.front_ecs_autoscaling.appautoscaling_target
    }
    tostring(aws_ecs_service.pdf.name) = {
      scale_down_to = 0
      scale_up_to   = var.account.autoscaling.pdf.maximum
      target        = module.pdf_ecs_autoscaling.appautoscaling_target
    }
  }
}
