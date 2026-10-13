# Service Deployment and Recovery

This document sets out instructions on how to recover failed deployments either by deploying a previous version of the service or forcing a redeployment.
These are the available options:

- [Force a redeployment of ecs services](#force-a-redeployment) (of the same version)
- [Deploy an earlier container version tag](#deploying-previous-container-versions-using-terraform) (terraform apply)
- [Deploy an earlier version of the infrastructure and containers](#deploy-an-earlier-version-of-the-infrastructure-and-containers) (check out a git tag and apply)
- [Revert a Pull Request](#revert-a-pull-request) (relying on Github Actions to handle deployment)

## Prerequisites

You will need the aws cli tool installed.

```bash
brew install awscli
```

See [aws-vault-assumable-roles](../aws_vault_assumable_roles/aws_vault_assumable_roles.md) for details on how to setup your aws-vault roles needed for running aws cli commands.

## Force a redeployment

This forces redeployment of the containers in the current ECS task definition. Use this when a service is unhealthy and restarting its current tasks may resolve the issue. This does not deploy a different container version or change infrastructure.

The ECS cluster name is the Terraform workspace name followed by `-online-lpa`. For example, workspace `3872lpal2484update` uses cluster `3872lpal2484update-online-lpa`.

``` bash
for service in api admin pdf front; \
do aws-vault exec <aws-vault-profile> -- \
aws ecs update-service --cluster <cluster-name> \
--force-new-deployment --service $service; done
```

For example,

```bash
for service in api admin pdf front; \
do aws-vault exec ual-dev -- \
aws ecs update-service --cluster 3872lpal2484update-online-lpa \
--force-new-deployment --service $service; done
```

This will start a redeployment of the ECS service, starting with bringing the new tasks up with the latest task definition, and once they are healthy, stopping the old tasks.

## Deploying previous container versions using Terraform

Use this if you need to deploy a known earlier container version without changing Terraform infrastructure.

### Find a container version tag

This command lists the 20 most recently pushed tags for the front end image, newest first. Use the tag for the version you want to deploy.

```bash
aws-vault exec management -- \
aws ecr describe-images \
--repository-name online-lpa/front_web \
--filter tagStatus=TAGGED \
--query "reverse(sort_by(imageDetails,&imagePushedAt))[:20].[imagePushedAt, join(', ', imageTags)]" \
--output table
```

**Note:** the `container_version` variable defines the version to use for all containers.

Checkout and pull the branch relevant to the environment.

e.g.

``` bash
git checkout UML-4540 && git pull
```

In the `<project root>/terraform/environment` folder, load the Terraform environment configuration before running the commands below. This requires `direnv` and sets the Terraform role variables and PagerDuty token.

**Note:** for production and preproduction, consult with a WebOps Engineer as this will require `breakglass` access.

```bash
direnv allow
```

Run Terraform as the `identity` profile. Terraform assumes the required roles for the environment and management accounts.

```bash
# Select the appropriate workspace in terraform.
aws-vault exec identity -- \
terraform workspace select <workspace-name>

# Initialise terraform
aws-vault exec identity -- \
terraform init

# Plan terraform with container versions
aws-vault exec identity -- \
terraform plan -var container_version=<container-version>

# Apply terraform. if happy type yes when prompted.
aws-vault exec identity -- \
terraform apply -var container_version=<container-version>
```

e.g:

```bash
# Select the appropriate workspace in terraform.
aws-vault exec identity -- \
terraform workspace select 3872lpal2484update

# Initialise terraform
aws-vault exec identity -- \
terraform init

# Plan terraform with container version
aws-vault exec identity -- \
terraform plan -var container_version=3872lpal2484update

# Apply terraform. if happy type yes when prompted.
aws-vault exec identity -- \
terraform apply -var container_version=3872lpal2484update
```

This switch over might take a few minutes after the apply to drain the old ECS container version and replace.

### Check deployment status

Wait for each ECS service to become stable. This command completes with no output when the service is stable:

```bash
for service in api admin pdf front; \
do aws-vault exec ual-dev -- \
aws ecs wait services-stable --cluster 3872lpal2484update-online-lpa \
--services $service; done
```

You can also use the following command to show the most recent ECS service events e.g. "(service actor-service) has reached a steady state.". Events are shown newest first:

```bash
aws-vault exec ual-dev -- \
aws ecs describe-services --cluster 3872lpal2484update-online-lpa \
--services actor-service \
--query 'services[0].events[:5].[createdAt,message]' \
--output table
```

## Deploy an earlier version of the infrastructure and containers

Use this if a container-only rollback does not resolve the issue and you need to restore the Terraform configuration from a previous release.

Do not continue if the Terraform plan contains unexpected changes to stateful resources, such as DynamoDB tables, S3 buckets etc.

Fetch the release tags and choose the version to deploy. Release tags use the format `v<major>.<minor>.<patch>`.

```bash
git fetch --tags origin
git tag --sort=-creatordate | head -20
```

Before checking out the earlier version, record the current branch and commit so that you can return to it after the rollback.

```bash
git branch --show-current
git rev-parse HEAD
```

Check out the release tag. For example:

```bash
git checkout v0.327.130
```

If the release included changes in `terraform/account`, roll those changes back before the environment changes:

In the `<project root>/terraform/account` folder, load the Terraform environment configuration, select the appropriate workspace and plan the rollback:

```bash
direnv allow

aws-vault exec identity -- \
terraform workspace select <workspace-name>

aws-vault exec identity -- \
terraform init

aws-vault exec identity -- \
terraform plan
```

Review the plan carefully. If it contains only the expected account-level changes, apply it:

```bash
aws-vault exec identity -- \
terraform apply
```

In the `<project root>/terraform/environment` folder, load the Terraform environment configuration, select the workspace to roll back and initialise Terraform.

```bash
direnv allow

aws-vault exec identity -- \
terraform workspace select <workspace-name>

aws-vault exec identity -- \
terraform init
```

The container tag for a release is `main-` followed by the Git tag. Plan the deployment with the matching Terraform configuration and container versions.

```bash
aws-vault exec identity -- \
terraform plan -var container_version=main-v0.327.130
```

Review the plan carefully. If it only contains the expected infrastructure and ECS task definition changes, apply it:

```bash
aws-vault exec identity -- \
terraform apply -var container_version=main-v0.327.130
```

After applying, use the [Check deployment status](#check-deployment-status) section to confirm that the ECS services are stable.

When the rollback is complete, return to the branch and commit recorded earlier.

## Revert a Pull Request

Use this when the issue was introduced by a merged PR and reverting its code is safer than manually changing the deployed version.  The steps below are for creating the revert PR from the terminal, but it's usually easier to revert via the GitHub UI.

Update your local `main` branch and create a branch for the revert:

```bash
git checkout main
git pull origin main
git checkout -b revert-pr-<pull-request-number>
```

Find the merge commit for the pull request:

```bash
git log
```

Revert the merge commit. The `-m 1` option keeps the `main` branch as the parent and reverses the pull request changes.

```bash
git revert -m 1 <merge-commit-sha>
```

Push the revert branch:

```bash
git push -u origin revert-pr-<pull-request-number>
```

Create a pull request for the revert:

```bash
gh pr create \
--base main \
--head revert-pr-<pull-request-number> \
--title "Revert pull request #<pull-request-number>" \
--body "Reverts <merge-commit-sha> to recover from <incident-or-issue>."
```

After the revert pull request is reviewed and merged, monitor the normal deployment pipeline and confirm the service recovers.
