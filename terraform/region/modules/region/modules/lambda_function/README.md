## Requirements

No requirements.

## Providers

| Name | Version |
|------|---------|
| aws | n/a |

## Modules

No Modules.

## Resources

| Name |
|------|
| [aws_iam_policy_document](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/iam_policy_document) |
| [aws_iam_role](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/iam_role) |
| [aws_iam_role_policy](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/iam_role_policy) |
| [aws_iam_role_policy_attachment](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/iam_role_policy_attachment) |
| [aws_lambda_function](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/lambda_function) |

## Inputs

| Name | Description | Type | Default | Required |
|------|-------------|------|---------|:--------:|
| command | The CMD for the docker image. | `list(string)` | `null` | no |
| description | Description of your Lambda Function (or Layer) | `string` | `""` | no |
| entry\_point | The ENTRYPOINT for the docker image. | `list(string)` | `null` | no |
| environment\_variables | A map that defines environment variables for the Lambda Function. | `map(string)` | `{}` | no |
| image\_uri | The URI for the coontainer image to use | `string` | `null` | no |
| lambda\_name | A unique name for your Lambda Function | `string` | n/a | yes |
| lambda\_role\_policy\_document | The inline policy document for the lambda IAM role. This is a JSON formatted string. | `string` | n/a | yes |
| package\_type | The Lambda deployment package type. | `string` | `"Image"` | no |
| tags | A map of tags to assign to resources. | `map(string)` | `{}` | no |
| timeout | The amount of time your Lambda Function has to run in seconds. | `number` | `3` | no |
| working\_directory | The working directory for the docker image. | `string` | `null` | no |

## Outputs

No output.
