resource "aws_resourcegroups_group" "environment" {
  name = "lpa-${local.environment}"

  resource_query {
    query = local.environment_resource_group_query
  }
}

locals {
  environment_resource_group_query = jsonencode({
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
