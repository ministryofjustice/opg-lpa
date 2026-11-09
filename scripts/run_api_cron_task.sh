#!/usr/bin/env bash
# Runs a one-off command in the api-cron ECS task of a development-account environment.
# Expects AWS credentials in the environment (e.g. via aws-vault).
# Usage: scripts/run_api_cron_task.sh <environment> <command> [args...]

set -euo pipefail

env_name="${1:?Usage: $0 <environment> <command> [args...]}"
shift

if [[ "$env_name" == "production" || "$env_name" == "preproduction" ]]; then
  echo "Refusing to run against ${env_name}" >&2
  exit 1
fi

cluster="${env_name}-online-lpa"

# Reuse the API service's network config, plus the RDS client group so the task can reach Postgres
network=$(aws ecs describe-services --cluster "$cluster" --services api \
  --query "services[0].networkConfiguration" --output json)
rds_sg=$(aws ec2 describe-security-groups \
  --filters "Name=group-name,Values=rds-client-${env_name}*" \
  --query "SecurityGroups[0].GroupId" --output text)
if [[ "$rds_sg" != "None" ]]; then
  network=$(jq -c --arg sg "$rds_sg" \
    '.awsvpcConfiguration.securityGroups |= ((. + [$sg]) | unique)' <<<"$network")
fi

overrides=$(jq -cn '{containerOverrides: [{name: "app", command: $ARGS.positional}]}' --args -- "$@")

task=$(aws ecs run-task --cluster "$cluster" --launch-type FARGATE --platform-version 1.4.0 \
  --task-definition "${env_name}-api-cron" --network-configuration "$network" \
  --overrides "$overrides" --query "tasks[0].taskArn" --output text)
echo "Started ${task}"

aws ecs wait tasks-stopped --cluster "$cluster" --tasks "$task"
code=$(aws ecs describe-tasks --cluster "$cluster" --tasks "$task" \
  --query "tasks[0].containers[0].exitCode" --output text)

echo "exit code: ${code}"
echo "Logs: CloudWatch log group of the api-cron task definition, stream suffix ${task##*/}"
[[ "$code" == "0" ]]
