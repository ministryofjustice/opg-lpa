#!/usr/bin/env bash

if [ $# -eq 0 ]; then
    echo "Please provide workspaces to be removed."
fi

if [ "$1" == "-h" ]; then
  echo "Usage: `basename $0` [workspaces separated by a space]"
  exit 0
fi

RESPONSE=$(curl -X DELETE -s -o /dev/null -w "%{http_code}" -H "Accept: application/vnd.github+json" -H "Authorizartion: Bearer ${GITHUB_TOKEN}" https://api.github.com/repos/ministryofjustice/opg-lpa/environments/$1)

if [ "${RESPONSE}" == "204" ]; then
  echo "Environment $1 deleted"
  exit 0
else
  echo "Environment $1 not deleted"
  exit 1
fi