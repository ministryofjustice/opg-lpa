
resource "aws_iam_policy" "backup_account_key" {
  name        = "backup_account_kms_key_policy"
  description = "KMS policy for cross account backup"
  policy      = data.aws_iam_policy_document.backup_account_key.json
}


# data "aws_iam_policy_document" "backup_account_key" {
#   provider = aws.backup_account
#   statement {
#     sid       = "Enable Root account permissions on Key"
#     effect    = "Allow"
#     actions   = ["kms:*"]
#     resources = ["*"]

#     principals {
#       type = "AWS"
#       identifiers = [
#         "arn:aws:iam::${data.aws_caller_identity.backup_account.account_id}:root",
#       ]
#     }
#   }

#   statement {
#     sid       = "Allow Key to be used for Encryption"
#     effect    = "Allow"
#     resources = ["*"]
#     actions = [
#       "kms:CreateGrant",
#       "kms:Decrypt",
#       "kms:GenerateDataKey*",
#       "kms:DescribeKey"
#     ]

#     principals {
#       type        = "AWS"
#       identifiers = ["*"]
#     }
#     condition {
#       test     = "StringEquals"
#       variable = "kms:CallerAccount"
#       values   = [data.aws_caller_identity.current.account_id]
#     }
#   }

#   statement {
#     sid       = "Key Administrator"
#     effect    = "Allow"
#     resources = ["*"]
#     actions = [
#       "kms:Create*",
#       "kms:Describe*",
#       "kms:Enable*",
#       "kms:List*",
#       "kms:Put*",
#       "kms:Update*",
#       "kms:Revoke*",
#       "kms:Disable*",
#       "kms:Get*",
#       "kms:Delete*",
#       "kms:TagResource",
#       "kms:UntagResource",
#       "kms:ScheduleKeyDeletion",
#       "kms:CancelKeyDeletion"
#     ]

#     principals {
#       type        = "AWS"
#       identifiers = ["arn:aws:iam::${data.aws_caller_identity.backup_account.account_id}:role/breakglass"]
#     }
#   }
# }


# # resource "aws_kms_key" "backup_account_key" {
# #   description             = "cross account backup encryption key"
# #   deletion_window_in_days = 7
# #   enable_key_rotation     = true
# #   policy                  = data.aws_iam_policy_document.backup_account_key.json
# #   multi_region            = true
# #   provider                = aws.backup_account
# # }

# # resource "aws_kms_alias" "backup_account_key" {
# #   name          = "alias/mrk-rds-cross-account-backup-key"
# #   target_key_id = aws_kms_key.backup_account_key.key_id
# #   provider      = aws.backup_account
# # }
