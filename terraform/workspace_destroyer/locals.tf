variable container_version {
  default = "latest"
}

locals {
  opg_project  = "lpa-workspace-destroyer"
  account_name = "development"
  environment  = "workspace_destroyer"
  account_id   = "050256574573"

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = false
  }

  optional_tags = {
    environment-name       = "development"
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
