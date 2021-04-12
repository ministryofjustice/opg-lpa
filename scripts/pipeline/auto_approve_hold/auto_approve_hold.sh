#!/usr/bin/env bash
set -euo pipefail

ID="$1"
HOLD_JOB_NAME="$2"
echo "ID: ${ID}"
echo "HOLD_JOB_NAME: ${HOLD_JOB_NAME}"
url="https://circleci.com/api/v2/workflow/${ID}/job"

# Get workflow details
workflow=$(curl -s -u "${CIRCLE_TOKEN}": -X GET --header "Content-Type: application/json" "$url")

# Get approval job id
job_id=$(echo "${workflow}" | jq -r '.items[] | select(.name=="${HOLD_JOB_NAME}") | .approval_request_id ')

echo "allowing approval. Job ID: ${job_id}"

# Approve
curl \
  --header "Content-Type: application/json" \
  -u "${CIRCLE_TOKEN}:" \
  -X POST \
  "https://circleci.com/api/v2/workflow/${ID}/approve/${job_id}"
