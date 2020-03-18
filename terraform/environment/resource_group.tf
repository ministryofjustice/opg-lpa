resource "aws_resourcegroups_group" "environment" {
  name = "lpa-${local.environment}"

  resource_query {
    query = <<JSON
{
  "ResourceTypeFilters": [
    "AWS::AllSupported"
  ],
  "TagFilters": [
    {
      "Key": "Name",
      "Values": ["${local.environment}-online-lpa-tool"]
    }
  ]
}
JSON
  }
}
