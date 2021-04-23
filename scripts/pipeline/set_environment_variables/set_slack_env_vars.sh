#! /bin/bash

echo 'export FRONT_DOMAIN="$(jq -r .front_fqdn /tmp/environment_pipeline_tasks_config.json)"'
echo 'export ADMIN_DOMAIN="$(jq -r .admin_fqdn /tmp/environment_pipeline_tasks_config.json)"'
echo 'export PUBLIC_FACING_DOMAIN="$(jq -r .public_facing_fqdn /tmp/environment_pipeline_tasks_config.json)"'
echo 'export COMMIT_MESSAGE="$(git log -1 --pretty=format:"%s (%h)")"'
