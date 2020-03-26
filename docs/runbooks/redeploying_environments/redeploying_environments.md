# Redeploying an environment

This document sets out how to perform a forced redeployment of ECS services using aws-cli.

## Prerequisites

You will need to have a working installation of aws-vault (see <https://ministryofjustice.github.io/opg-new-starter/amazon.html#aws-vault>), configured with an identity profile.

You will also need the aws-cli tool, which can be installed with homebrew.

``` bash
brew install awscli
```

## Setting up credentials

The ECS services redeployment will be performed with `aws-cli`. To assume the correct IAM role to perform the redeploy, we will add new profiles to the aws connfiguration stored in `~/.aws/config`.

Copy the following into your AWS config found at `~/.aws/config`.
Replace `<aws-username>` with your AWS user name.

``` yaml
[profile moj-lpa-dev]
region=eu-west-1
role_arn=arn:aws:iam::050256574573:role/operator
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<aws-username>

[profile moj-lpa-preprod]
region=eu-west-1
role_arn=arn:aws:iam::987830934591:role/breakglass
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<aws-username>

[profile moj-lpa-prod]
region=eu-west-1
role_arn=arn:aws:iam::980242665824:role/breakglass
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<aws-username>
```

Once added you will be able to see the new profiles available for use in aws-vault.

``` bash
aws-vault list
```

## Issuing a force redeploy command for a service

First, determine the correct aws-vault profile to use that matches the environment to be redeployed.

`moj-lpa-dev` for all development account environments
`moj-lpa-preprod` for preproduction
`moj-lpa-prod` for production

Next, get the cluster name. This will be the terraform workspace name with `-online-lpa` suffixed.

For example,
`76-LPA3435Bug-online-lpa`
`preproduction-online-lpa`
`production-online-lpa`

Substitute the parameters in `<>` with the appropriate values

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
