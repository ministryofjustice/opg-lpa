#!/usr/bin/env sh
set -euo pipefail

ID="$1"
HOLD_JOB_NAME="$2"
API_KEY="$3"

NEXT_PAGE_TOKEN=
WORKFLOW=
APPROVAL_ID=

echo "ID: ${ID}"
echo "HOLD_JOB_NAME: ${HOLD_JOB_NAME}"

function get_workflow_approval_by_name(){
    # Get workflow details

    urlbase="https://circleci.com/api/v2/workflow/${ID}/job?circle-token=${API_KEY}"
    url=urlbase
    if [[ -n "$1" ]]
    then
        echo "looking at next page in workflow..."
        url="${url}&page_token=$1"
    fi

    WORKFLOW=$(curl -s -X GET --header "Content-Type: application/json" "$url")
    $NEXT_PAGE_TOKEN=(echo ${WORKFLOW} | jq -r '.next_page_token')

    # Get approval job id
    APPROVAL_ID=$(echo "${WORKFLOW}" | jq -r --arg HOLD_JOB_NAME "${HOLD_JOB_NAME}"  '.items[] | select(.name==$HOLD_JOB_NAME) | .approval_request_id')
}

function approve_job(){
    echo "allowing approval ID: ${APPROVAL_ID}"

    # Approve
    curl \
    --header "Content-Type: application/json" \
    -X POST \
    "https://circleci.com/api/v2/workflow/${ID}/approve/${APPROVAL_ID}?circle-token=${API_KEY}"
}

until  [[ "${NEXT_PAGE_TOKEN}" == "null" ]]
do
    get_workflow_approval_by_name "${NEXT_PAGE_TOKEN}"
    if [[ -n "${APPROVAL_ID}" ]];
    then
        echo "found approval ID: ${APPROVAL_ID}"
        break
    fi
done

if [[ -n "${APPROVAL_ID}" ]]
then
    approve_job
else
    echo approval step not found for ${HOLD_JOB_NAME}
    exit 1
fi
