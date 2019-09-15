# resource "aws_ecs_cluster" "workspace_destroyer" {
#   name = "workspace_destroyer"
#   tags = local.default_tags
# }


# resource "aws_iam_role" "workspace_destroyer_execution_role" {
#   name               = "workspace_destroyer_execution_role"
#   assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
#   tags               = local.default_tags
# }

# data "aws_iam_policy_document" "ecs_assume_policy" {
#   statement {
#     effect  = "Allow"
#     actions = ["sts:AssumeRole"]

#     principals {
#       identifiers = ["ecs-tasks.amazonaws.com"]
#       type        = "Service"
#     }
#   }
# }

# resource "aws_iam_role_policy" "workspace_destroyer_execution_role" {
#   name   = "workspace_destroyer_execution_role"
#   policy = data.aws_iam_policy_document.workspace_destroyer_execution_role.json
#   role   = aws_iam_role.workspace_destroyer_execution_role.id
# }

# data "aws_iam_policy_document" "workspace_destroyer_execution_role" {
#   statement {
#     effect    = "Allow"
#     resources = ["*"]
#     actions = [
#       "ecr:GetAuthorizationToken",
#       "ecr:BatchCheckLayerAvailability",
#       "ecr:GetDownloadUrlForLayer",
#       "ecr:BatchGetImage",
#       "logs:CreateLogStream",
#       "logs:PutLogEvents",
#     ]
#   }
#   statement {
#     effect    = "Allow"
#     resources = ["*"]
#     actions = [
#       "elasticloadbalancing:DeregisterInstancesFromLoadBalancer",
#       "elasticloadbalancing:DeregisterTargets",
#       "elasticloadbalancing:Describe*",
#       "elasticloadbalancing:RegisterInstancesWithLoadBalancer",
#       "elasticloadbalancing:RegisterTargets",
#     ]
#   }
#   statement {
#     effect    = "Allow"
#     resources = ["*"]
#     actions   = ["ssm:GetParameters"]
#   }
# }



