#!/usr/bin/env bash

# A script to destroy all workspaces except for the ones passed in as arguments
# Usage: ./workspace_cleanup.sh <workspace1> <workspace2> <workspace3> ...

set -Eeuo pipefail

print_usage() {
  echo "Usage: `basename $0` [workspace1] [workspace2] [workspace3] ..."
}

if [ $# -eq 0 ]; then
  print_usage
  exit 1
fi

if [ "$1" == "-h" ]; then
  print_usage
  exit 0
fi

in_use_workspaces="$@"
reserved_workspaces="default production preproduction development demo ithc"

protected_workspaces="$in_use_workspaces $reserved_workspaces"
all_workspaces=$(terraform workspace list|sed 's/*//g')

for workspace in $all_workspaces
do
  case "$protected_workspaces" in
    *$workspace*)
      echo "protected workspace: $workspace"
      ;;
    *)
      echo "cleaning up workspace $workspace..."
      terraform workspace select $workspace
      terraform init
      terraform refresh
      terraform destroy -auto-approve
      terraform workspace select default
      terraform workspace delete $workspace
      ;;
  esac
done
