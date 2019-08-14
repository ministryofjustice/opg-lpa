# Terraform Shared

This terraform configuration manages per-environment resources.

Per-account or otherwise shared resources are managed in `../terraform_account`

## Namespace resources
It is important to namespace resources to avoid getting errors for creating resources that already exist.

The preferred variable for namespacing is `local.environment`. `terraform.workspace` is also available if `local.environment` cannot be used.

`${local.environment}`
returns the environment name

```
"${local.environment}-online-lpa"
``` 
can return `LPA-93-online-lpa` or `production-online-lpa`

## Running Terraform Locally

This repository comes with an `.envrc` file containing useful environment variables for working with this repository.

`.envrc` can be sourced automatically using either [direnv](https://direnv.net) or manually with bash.

```bash
source .envrc
```

```bash
direnv allow
```

This sets environment variables that allow the following commands with no further setup

```bash
aws-vault exec identity -- terraform init
aws-vault exec identity -- terraform plan
aws-vault exec identity -- terraform force-unlock 49b3784c-51eb-668d-ac4b-3bd5b8701925
```

## Fixing state lock issue
A Terraform state lock error can happen if a terraform job is forcefully terminated (normal ctrl+c gracefully releases state lock).

CircleCI terminates a process if you cancel a job, so state lock doesn't get released.

Here's how to fix it if it happens.
Error: 

```hsl
rror locking state: Error acquiring the state lock: ConditionalCheckFailedException: The conditional request failed
    status code: 400, request id: 60Q304F4TMIRB13AMS36M49ND7VV4KQNSO5AEMVJF66Q9ASUAAJG
Lock Info:
  ID:        69592de7-6132-c863-ae53-976776ffe6cf
  Path:      opg.terraform.state/env:/development/moj-lasting-power-of-attorney-environment/terraform.tfstate"
  Operation: OperationTypeApply
  Who:       @d701fcddc381
  Version:   0.11.13
  Created:   2019-05-09 16:01:50.027392879 +0000 UTC
  Info:
```      

Fix:
```hsl
aws-vault exec identity -- terraform init
aws-vault exec identity -- terraform workspace select development
aws-vault exec identity -- terraform force-unlock 69592de7-6132-c863-ae53-976776ffe6cf
```

It is important to select the correct workspace. 
For terraform_environment, this will be based on your PR and can be found in the CircleCI pipeline job dev_apply_environment_terraform

In the example below the workspace name is `48-LPA116appl`

```
#!/bin/sh -eo pipefail
ENV_NAME=${CIRCLE_PULL_REQUEST##*/}-${CIRCLE_BRANCH//-/}
export TF_WORKSPACE=${ENV_NAME:0:13} >> $BASH_ENV
echo $TF_WORKSPACE
export SHORT_HASH=${CIRCLE_SHA1:0:7} >> $BASH_ENV
echo $SHORT_HASH
cd terraform_environment
terraform init
terraform apply --auto-approve -var container_version=$CIRCLE_BRANCH-$SHORT_HASH
if [ "${CIRCLE_BRANCH}" != "master" ]; then
  echo "Your environment, ${ENV_NAME:0:13} is built."
  echo "To destroy this environment"
  echo
  echo "cd terraform_environment"
  echo "aws-vault exec identity -- terraform init"
  echo "aws-vault exec identity -- terraform workspace select ${ENV_NAME:0:13}"
  echo "aws-vault exec identity -- terraform destroy"
fi

48-LPA116appl
147a764

Initializing the backend...
```

## Manually Creating Environments
start in the terraform_environment directory
```
cd terraform/terraform_environment
```
Modify the .envrc file to provide an environment name as a workspace
```
export TF_WORKSPACE=test-environment
```

apply the .envrc file
```
direnv allow
```

Use terraform to apply the environment
```
aws-vault exec identity -- terraform init
aws-vault exec identity -- terraform apply
```

Note that the variable for container version will default to `latest` from ECR. The CI/CD pipeline will build and deploy new containers.
