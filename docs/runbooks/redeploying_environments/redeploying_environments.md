# Redeploying an environment

This document sets out how to perform a forced redeployment of ECS services using aws-cli.

## Prerequisites

please see [Setting up AWS Credentials](../setting-up-aws-credentials/setting-up-credentials.md) for details of how to set up aws cli and aws-vault on your local machine.

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
