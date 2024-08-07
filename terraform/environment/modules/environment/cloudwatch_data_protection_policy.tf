resource "aws_cloudwatch_log_data_protection_policy" "application_logs" {

  log_group_name = "${var.environment_name}_application_logs"

  policy_document = jsonencode({
    Name    = "data_protection_${var.environment_name}_application_logs"
    Version = "2021-06-01"

    "Statement" : [
      {
        "Sid" : "audit-policy",
        "DataIdentifier" : [
          "arn:aws:dataprotection::aws:data-identifier/EmailAddress"
        ],
        "Operation" : {
          "Audit" : {
            "FindingsDestination" : {}
          }
        }
      },
      {
        "Sid" : "redact-policy",
        "DataIdentifier" : [
          "arn:aws:dataprotection::aws:data-identifier/EmailAddress"
        ],
        "Operation" : {
          "Deidentify" : {
            "MaskConfig" : {}
          }
        }
      }
    ]
  })
}
