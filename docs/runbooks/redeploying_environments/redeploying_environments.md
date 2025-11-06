# Redeploying an environment

This document sets out how to perform a forced redeployment of ECS services using aws-cli.

You will need to determine variable value replacements to use.

Then, there are 2 options:

- Issuing a force deploy of the latest tag for an ECS Service
- Using Terraform to apply a specific container version

## Prerequisites

please see [Setting up AWS Credentials](../setting_up_aws_credentials/setting_up_credentials.md) for details of how to set up aws cli and aws-vault on your local machine.

You will also need Terraform tools appropriately versioned as set in the .tfswitchrc on the root of the project.

## Determine variable value replacements to use

To determine the correct *aws-vault-profile* to use that matches the environment to be redeployed.

`moj-lpa-dev` for all development account environments
`moj-lpa-preprod` for preproduction
`moj-lpa-prod` for production

To determine the *workspace-name* for the environment, find the prefix of the environment from the URL's produced e.g.
`76-LPA3435Bug`
`preproduction`
`production`

To determine the *cluster-name*, This will be the terraform workspace name with `-online-lpa` suffixed.

For example,
`76-LPA3435Bug-online-lpa`
`preproduction-online-lpa`
`production-online-lpa`

To determine the *docker-tag* inspect one of the build step results for container builds.
The container builds will be tagged similar to `76-LPA3435Bug-cde321ba` for example.

Substitute the parameters in `<>` with the appropriate values

## Issuing a force redeploy command for a service

This forces redeployment of the `latest` tagged containers on the services.

``` bash
for service in api admin pdf front; \
do aws-vault exec <aws-vault-profile> -- \
aws ecs update-service --cluster <cluster-name> \
--force-new-deployment --service $service; done
```

For example,

```bash
for service in api admin pdf front; \
do aws-vault exec moj-lpa-dev -- \
aws ecs update-service --cluster 76-LPA3435Bug-online-lpa \
--force-new-deployment --service $service; done
```

This will start a redeployment of the ECS service, starting with bringing the new tasks up with the latest task definition, and once they are healthy, stopping the old tasks.

## Deploying specific container versions using Terraform

Use this if you want to specify the container tag that is to be deployed. This is in the scenario where the latest deployment contains a breaking change in the environment that means it is down e.g. production, and you want to rollback to the previous container version at short notice.

**Note:** if you are requiring **production** changes, consult with the webops engineer as this requires `breakglass` access, and they can assist as needed.
`
checkout and pull the relevant branch of this repo to the environment.

e.g:

``` bash
git checkout 76-LPA3435Bug && git pull
```

In the `<project root>/terraform/environment` folder, you will need to do the following:

Set the appropriate role for the AWS environment you are looking to deploy, and run terrfom commads to plan and aply the container version:

```bash
# Select the appropriate workspace in terraform.
 aws-vault exec <aws-vault-profile> -- \
 terraform workspace select <workspace-name>

# Initialise terraform
aws-vault exec <aws-vault-profile> -- \
terraform init

# Plan terraform with container version
aws-vault exec <aws-vault-profile> -- \
terraform plan -var container_version=<container-version>

# Apply terraform. if happy type yes when prompted.
aws-vault exec <aws-vault-profile> -- \
terraform apply -var container_version=<container-version>
```

e.g:

```bash
# Select the appropriate workspace in terraform.
 aws-vault exec moj-lpa-dev -- \
 terraform workspace select 76-LPA3435Bug

# Initialise terraform
aws-vault exec moj-lpa-dev -- \
terraform init

# Plan terraform with container version
aws-vault exec moj-lpa-dev -- \
terraform plan -var container_version=76-LPA3435Bug-cde321ba

# Apply terraform. if happy type yes when prompted.
aws-vault exec moj-lpa-dev -- \
terraform apply -var container_version=76-LPA3435Bug-cde321ba
```

This switch over might take a few minutes after the apply to drain the old ECS container version and replace.

You can  navigate to the ping/json endpoint on the service to see what container version is there to confirm.
You can navigate using a browser, or use `curl`
So, for the above example:

``` bash
curl https//76-LPA3435Bug.lastingpowerofattorney.gov.uk/ping/json
```

 will return something like this:

```json
{"dynamo":{"ok":true,"details":{"sessions":true,"locks":true}},"api":{"ok":true,"details":{"200":true,"database":{"ok":true},"gateway":{"ok":true},"ok":true,"queue":{"details":{"available":true,"length":0,"lengthAcceptable":true},"ok":true}}},"ok":true,"iterations":6,"tag":"76-LPA3435Bug-cde321ba"}
```

note the `tag` value should be the same as the requested container version in the plan and apply step.
