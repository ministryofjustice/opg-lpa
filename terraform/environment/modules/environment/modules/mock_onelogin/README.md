# Mock One Login Module

This module creates the resources required to mock One Login.

<!-- BEGIN_TF_DOCS -->
## Requirements

| Name | Version |
|------|---------|
| <a name="requirement_terraform"></a> [terraform](#requirement\_terraform) | >= 1.5.2 |
| <a name="requirement_aws"></a> [aws](#requirement\_aws) | ~> 5.42.0 |

## Providers

| Name | Version |
|------|---------|
| <a name="provider_aws.region"></a> [aws.region](#provider\_aws.region) | ~> 5.42.0 |

## Modules

No modules.

## Resources

| Name | Type |
|------|------|
| [aws_ecs_service.mock_onelogin](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/ecs_service) | resource |
| [aws_ecs_task_definition.mock_onelogin](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/ecs_task_definition) | resource |
| [aws_lb.mock_onelogin](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/lb) | resource |
| [aws_lb_listener.mock_onelogin_loadbalancer](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/lb_listener) | resource |
| [aws_lb_listener.mock_onelogin_loadbalancer_http_redirect](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/lb_listener) | resource |
| [aws_lb_listener_certificate.mock_onelogin_loadbalancer_live_service_certificate](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/lb_listener_certificate) | resource |
| [aws_lb_target_group.mock_onelogin](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/lb_target_group) | resource |
| [aws_security_group.mock_onelogin_ecs_service](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group) | resource |
| [aws_security_group.mock_onelogin_loadbalancer](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group) | resource |
| [aws_security_group_rule.loadbalancer_ingress_route53_healthchecks](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_security_group_rule.mock_one_login_service_app_ingress](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_security_group_rule.mock_onelogin_ecs_service_egress](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_security_group_rule.mock_onelogin_ecs_service_ingress](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_security_group_rule.mock_onelogin_loadbalancer_egress](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_security_group_rule.mock_onelogin_loadbalancer_ingress](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_security_group_rule.mock_onelogin_loadbalancer_port_80_redirect_ingress](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_security_group_rule.mock_onelogin_loadbalancer_public_access_ingress](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group_rule) | resource |
| [aws_service_discovery_service.mock_onelogin](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/service_discovery_service) | resource |
| [aws_acm_certificate.certificate_mock_onelogin](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/acm_certificate) | data source |
| [aws_default_tags.current](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/default_tags) | data source |
| [aws_ip_ranges.route53_healthchecks](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/ip_ranges) | data source |
| [aws_region.current](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/region) | data source |
| [aws_s3_bucket.access_log](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/s3_bucket) | data source |

## Inputs

| Name | Description | Type | Default | Required |
|------|-------------|------|---------|:--------:|
| <a name="input_alb_deletion_protection_enabled"></a> [alb\_deletion\_protection\_enabled](#input\_alb\_deletion\_protection\_enabled) | If true, deletion of the load balancer will be disabled via the AWS API. This will prevent Terraform from deleting the load balancer. Defaults to false. | `bool` | n/a | yes |
| <a name="input_app_ecs_service_security_group_id"></a> [app\_ecs\_service\_security\_group\_id](#input\_app\_ecs\_service\_security\_group\_id) | ID of the security group for the app ECS service | `string` | n/a | yes |
| <a name="input_aws_service_discovery_private_dns_namespace"></a> [aws\_service\_discovery\_private\_dns\_namespace](#input\_aws\_service\_discovery\_private\_dns\_namespace) | ID and name of the AWS Service Discovery private DNS namespace | <pre>object({<br>    id   = string<br>    name = string<br>  })</pre> | n/a | yes |
| <a name="input_container_port"></a> [container\_port](#input\_container\_port) | Port on the container to associate with. | `number` | n/a | yes |
| <a name="input_container_version"></a> [container\_version](#input\_container\_version) | Version of the container to use | `string` | n/a | yes |
| <a name="input_ecs_application_log_group_name"></a> [ecs\_application\_log\_group\_name](#input\_ecs\_application\_log\_group\_name) | The AWS Cloudwatch Log Group resource for application logging | `string` | n/a | yes |
| <a name="input_ecs_capacity_provider"></a> [ecs\_capacity\_provider](#input\_ecs\_capacity\_provider) | Name of the capacity provider to use. Valid values are FARGATE\_SPOT and FARGATE | `string` | n/a | yes |
| <a name="input_ecs_cluster"></a> [ecs\_cluster](#input\_ecs\_cluster) | ARN of an ECS cluster. | `string` | n/a | yes |
| <a name="input_ecs_execution_role"></a> [ecs\_execution\_role](#input\_ecs\_execution\_role) | ID and ARN of the task execution role that the Amazon ECS container agent and the Docker daemon can assume. | <pre>object({<br>    id  = string<br>    arn = string<br>  })</pre> | n/a | yes |
| <a name="input_ecs_service_desired_count"></a> [ecs\_service\_desired\_count](#input\_ecs\_service\_desired\_count) | Number of instances of the task definition to place and keep running. Defaults to 0. Do not specify if using the DAEMON scheduling strategy. | `number` | `0` | no |
| <a name="input_ecs_task_role"></a> [ecs\_task\_role](#input\_ecs\_task\_role) | ARN of IAM role that allows your Amazon ECS container task to make calls to other AWS services. | `any` | n/a | yes |
| <a name="input_ingress_allow_list_cidr"></a> [ingress\_allow\_list\_cidr](#input\_ingress\_allow\_list\_cidr) | List of CIDR ranges permitted to access the service | `list(string)` | n/a | yes |
| <a name="input_network"></a> [network](#input\_network) | VPC ID, a list of application subnets, and a list of private subnets required to provision the ECS service | <pre>object({<br>    vpc_id              = string<br>    application_subnets = list(string)<br>    public_subnets      = list(string)<br>  })</pre> | n/a | yes |
| <a name="input_public_access_enabled"></a> [public\_access\_enabled](#input\_public\_access\_enabled) | Enable access to the Modernising LPA service from the public internet | `bool` | n/a | yes |
| <a name="input_redirect_base_url"></a> [redirect\_base\_url](#input\_redirect\_base\_url) | Base URL expected for redirect\_url | `string` | n/a | yes |
| <a name="input_repository_url"></a> [repository\_url](#input\_repository\_url) | URL of the repository for the container to use | `string` | n/a | yes |

## Outputs

| Name | Description |
|------|-------------|
| <a name="output_ecs_service"></a> [ecs\_service](#output\_ecs\_service) | n/a |
| <a name="output_load_balancer"></a> [load\_balancer](#output\_load\_balancer) | n/a |
| <a name="output_load_balancer_security_group"></a> [load\_balancer\_security\_group](#output\_load\_balancer\_security\_group) | n/a |
<!-- END_TF_DOCS -->
