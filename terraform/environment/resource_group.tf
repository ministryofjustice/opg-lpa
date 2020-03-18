resource "aws_resourcegroups_group" "environment" {
  name = "lpa-${local.environment}"

  resource_query {
    query = local.rsource_group_environment_query
  }
}

locals {
  rsource_group_environment_query = jsonencode({
    ResourceTypeFilters = [
      "AWS::AllSupported"
    ],
    TagFilters = [
      {
        Key    = "Name",
        Values = ["${local.environment}-online-lpa-tool"]
      }
    ]
  })
}
