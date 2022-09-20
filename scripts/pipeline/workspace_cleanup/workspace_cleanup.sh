#!/usr/bin/env bash

if [ $# -eq 0 ]
  then
    echo "Please provide workspaces to be removed."
fi

if [ "$1" == "-h" ]; then
  echo "Usage: `basename $0` [workspaces separated by a space]"
  exit 0
fi

function getWorkspaces {
  terraform workspace list
}

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
