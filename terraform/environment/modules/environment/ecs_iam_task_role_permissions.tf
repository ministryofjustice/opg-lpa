// API

resource "aws_iam_role_policy" "api_permissions_role" {
  name   = "${var.environment_name}-${local.region_name}-apiApplicationPermissions"
  policy = data.aws_iam_policy_document.api_permissions_role.json
  role   = var.ecs_iam_task_roles.api.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "api_permissions_role" {
  statement {
    sid = "DynamoDBAccess"

    effect = "Allow"

    actions = [
      "dynamodb:BatchGetItem",
      "dynamodb:BatchWriteItem",
      "dynamodb:DeleteItem",
      "dynamodb:DescribeStream",
      "dynamodb:DescribeTable",
      "dynamodb:GetItem",
      "dynamodb:GetRecords",
      "dynamodb:GetShardIterator",
      "dynamodb:ListStreams",
      "dynamodb:ListTables",
      "dynamodb:PutItem",
      "dynamodb:Query",
      "dynamodb:Scan",
      "dynamodb:UpdateItem",
      "dynamodb:UpdateTable",
    ]

    resources = [
      aws_dynamodb_table.lpa-locks.arn,
      aws_dynamodb_table.lpa-properties.arn,
      aws_dynamodb_table.lpa-sessions.arn,
    ]
  }

  statement {
    sid = "APIGatewayAccess"
    actions = [
      "execute-api:Invoke",
    ]

    resources = [
      var.account.sirius_api_gateway_arn,
      var.account.sirius_api_healthcheck_arn,
    ]
  }
  statement {
    sid = "s3AccessWrite"
    actions = [
      "s3:PutObject",
      "s3:GetObject",
      "s3:DeleteObject",
      "s3:ListObject",
    ]
    #tfsec:ignore:aws-iam-no-policy-wildcards - Wildcard required for PutObject
    resources = [
      "${data.aws_s3_bucket.lpa_pdf_cache.arn}*",
    ]
  }
  statement {
    sid = "s3AccessRead"
    actions = [
      "s3:ListObject",
      "s3:ListBucket",
      "s3:GetObject",
    ]

    resources = [
      data.aws_s3_bucket.lpa_pdf_cache.arn,
    ]
  }
  statement {
    sid    = "lpaCacheDecrypt"
    effect = "Allow"
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]
    resources = [
      data.aws_s3_bucket.lpa_pdf_cache.arn,
      data.aws_kms_key.lpa_pdf_cache.arn,
    ]
  }
  statement {
    sid    = "lpaQueueDecrypt"
    effect = "Allow"
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]
    resources = [
      data.aws_kms_key.lpa_pdf_sqs.arn,
    ]
  }
  statement {
    effect = "Allow"
    sid    = "ApiXrayDaemon"
    #tfsec:ignore:aws-iam-no-policy-wildcards - Wildcard required for Xray
    resources = ["*"]

    actions = [
      "xray:PutTraceSegments",
      "xray:PutTelemetryRecords",
      "xray:GetSamplingRules",
      "xray:GetSamplingTargets",
      "xray:GetSamplingStatisticSummaries",
    ]
  }
}

// Admin
resource "aws_iam_role_policy" "admin_permissions_role" {
  name   = "${var.environment_name}-${local.region_name}-adminApplicationPermissions"
  policy = data.aws_iam_policy_document.admin_permissions_role.json
  role   = var.ecs_iam_task_roles.admin.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "admin_permissions_role" {
  statement {
    sid = "DynamoDBAccess"

    effect = "Allow"

    actions = [
      "dynamodb:BatchGetItem",
      "dynamodb:BatchWriteItem",
      "dynamodb:DeleteItem",
      "dynamodb:DescribeStream",
      "dynamodb:DescribeTable",
      "dynamodb:GetItem",
      "dynamodb:GetRecords",
      "dynamodb:GetShardIterator",
      "dynamodb:ListStreams",
      "dynamodb:ListTables",
      "dynamodb:PutItem",
      "dynamodb:Query",
      "dynamodb:Scan",
      "dynamodb:UpdateItem",
      "dynamodb:UpdateTable",
    ]

    resources = [
      aws_dynamodb_table.lpa-locks.arn,
      aws_dynamodb_table.lpa-properties.arn,
      aws_dynamodb_table.lpa-sessions.arn,
    ]
  }
  statement {
    effect = "Allow"
    sid    = "ApiXrayDaemon"
    #tfsec:ignore:aws-iam-no-policy-wildcards - Wildcard required for Xray
    resources = ["*"]

    actions = [
      "xray:PutTraceSegments",
      "xray:PutTelemetryRecords",
      "xray:GetSamplingRules",
      "xray:GetSamplingTargets",
      "xray:GetSamplingStatisticSummaries",
    ]
  }
}

resource "aws_iam_role_policy" "front_permissions_role" {
  name   = "${var.environment_name}-${local.region_name}-frontApplicationPermissions"
  policy = data.aws_iam_policy_document.front_permissions_role.json
  role   = var.ecs_iam_task_roles.front.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "front_permissions_role" {
  statement {
    sid = "DynamoDBAccess"

    effect = "Allow"

    actions = [
      "dynamodb:BatchGetItem",
      "dynamodb:BatchWriteItem",
      "dynamodb:DeleteItem",
      "dynamodb:DescribeStream",
      "dynamodb:DescribeTable",
      "dynamodb:GetItem",
      "dynamodb:GetRecords",
      "dynamodb:GetShardIterator",
      "dynamodb:ListStreams",
      "dynamodb:ListTables",
      "dynamodb:PutItem",
      "dynamodb:Query",
      "dynamodb:Scan",
      "dynamodb:UpdateItem",
      "dynamodb:UpdateTable",
    ]

    resources = [
      aws_dynamodb_table.lpa-locks.arn,
      aws_dynamodb_table.lpa-properties.arn,
      aws_dynamodb_table.lpa-sessions.arn,
    ]
  }
  statement {
    sid    = "lpaCacheDecrypt"
    effect = "Allow"
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]
    resources = [
      data.aws_s3_bucket.lpa_pdf_cache.arn,
      data.aws_kms_key.lpa_pdf_cache.arn,
    ]
  }
  statement {
    effect = "Allow"
    sid    = "ApiXrayDaemon"
    #tfsec:ignore:aws-iam-no-policy-wildcards - Wildcard required for Xray
    resources = ["*"]

    actions = [
      "xray:PutTraceSegments",
      "xray:PutTelemetryRecords",
      "xray:GetSamplingRules",
      "xray:GetSamplingTargets",
      "xray:GetSamplingStatisticSummaries",
    ]
  }
}

resource "aws_iam_role_policy" "pdf_permissions_role" {
  name   = "${var.environment_name}-${local.region_name}-pdfApplicationPermissions"
  policy = data.aws_iam_policy_document.pdf_permissions_role.json
  role   = var.ecs_iam_task_roles.pdf.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "pdf_permissions_role" {
  statement {
    sid    = "DynamoDBAccess"
    effect = "Allow"
    actions = [
      "dynamodb:BatchGetItem",
      "dynamodb:BatchWriteItem",
      "dynamodb:DeleteItem",
      "dynamodb:DescribeStream",
      "dynamodb:DescribeTable",
      "dynamodb:GetItem",
      "dynamodb:GetRecords",
      "dynamodb:GetShardIterator",
      "dynamodb:ListStreams",
      "dynamodb:ListTables",
      "dynamodb:PutItem",
      "dynamodb:Query",
      "dynamodb:Scan",
      "dynamodb:UpdateItem",
      "dynamodb:UpdateTable",
    ]

    resources = [
      aws_dynamodb_table.lpa-locks.arn,
      aws_dynamodb_table.lpa-properties.arn,
      aws_dynamodb_table.lpa-sessions.arn,
    ]
  }
  statement {
    sid = "S3AccessWrite"
    actions = [
      "s3:PutObject",
      "s3:GetObject",
      "s3:DeleteObject",
      "s3:ListObject",
    ]
    #tfsec:ignore:aws-iam-no-policy-wildcards - Wildcard required for PutObject
    resources = [
      "${data.aws_s3_bucket.lpa_pdf_cache.arn}*",
    ]
  }
  statement {
    sid = "s3AccessRead"
    actions = [
      "s3:ListObject",
      "s3:ListBucket",
      "s3:GetObject",
    ]

    resources = [
      data.aws_s3_bucket.lpa_pdf_cache.arn,
    ]
  }

  statement {
    sid    = "lpaCacheDecrypt"
    effect = "Allow"
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]
    resources = [
      data.aws_s3_bucket.lpa_pdf_cache.arn,
      data.aws_kms_key.lpa_pdf_cache.arn,
    ]
  }
  statement {
    sid    = "lpaQueueDecrypt"
    effect = "Allow"
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]
    resources = [
      data.aws_kms_key.lpa_pdf_sqs.arn,
    ]
  }
  statement {
    effect = "Allow"
    sid    = "ApiXrayDaemon"
    #tfsec:ignore:aws-iam-no-policy-wildcards - Wildcard required for Xray
    resources = ["*"]

    actions = [
      "xray:PutTraceSegments",
      "xray:PutTelemetryRecords",
      "xray:GetSamplingRules",
      "xray:GetSamplingTargets",
      "xray:GetSamplingStatisticSummaries",
    ]
  }
}
