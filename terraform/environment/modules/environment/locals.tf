locals {

  account_name_short = var.account.account_name_short

  cert_prefix_public_facing   = var.environment_name == "production" ? "www." : "*."
  cert_prefix_internal        = var.account_name == "production" ? "" : "*."
  dns_namespace_env           = var.environment_name == "production" ? "" : "${var.environment_name}."
  dns_namespace_env_public    = var.environment_name == "production" ? "www." : "${var.environment_name}."
  dns_namespace_dev_prefix    = var.account_name == "development" ? "development." : ""
  track_from_date             = "2019-04-01"
  front_dns                   = "front.lpa"
  admin_dns                   = "admin.lpa"
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  region_name                 = var.account.regions[data.aws_region.current.name].region
  is_primary_region           = var.account.regions[data.aws_region.current.name].is_primary

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = var.account.is_production
  }

  optional_tags = {
    environment-name       = var.environment_name
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-lpa/tree/master/docs/runbooks"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_opg_tags = merge(local.mandatory_moj_tags, local.optional_tags, {
    "Name" = "${var.environment_name}-online-lpa-tool"
  })

  performance_platform_component_tag = {
    component = "performance_platform"
  }

  shared_component_tag = {
    component = "shared"
  }

  admin_component_tag = {
    component = "admin"
  }

  front_component_tag = {
    component = "front"
  }

  api_component_tag = {
    component = "api"
  }

  dynamodb_component_tag = {
    component = "dynamodb"
  }

  db_component_tag = {
    component = "db"
  }

  pdf_component_tag = {
    component = "pdf"
  }

  seeding_component_tag = {
    component = "seeding"
  }

  feedbackdb_component_tag = {
    component = "feedbackdb"
  }

  aws_otel_collector = jsonencode(
    {
      cpu         = 0,
      essential   = true,
      image       = "public.ecr.aws/aws-observability/aws-otel-collector:v0.23.1",
      mountPoints = [],
      name        = "aws-otel-collector",
      command = [
        "--config=/etc/ecs/ecs-default-config.yaml"
      ],
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = var.region_name,
          awslogs-stream-prefix = "${var.environment_name}.otel.online-lpa"
        }
      }
    }
  )

}
