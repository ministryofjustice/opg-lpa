#!/usr/bin/env sh
set -euo pipefail
WEBHOOK=$1
PR=$2
STEP=$3
BUILD_URL=$4
APPROVAL_STEP=$5

echo "notifying slack..."

generate_payload()
{
    cat <<EOF
{
    "attachments" :
    [
        {
            "blocks" : [],
            "mrkdwn_in": ["pretext"],
            "pretext" : "<!here> :sign-warning: CircleCI Pipeline Approval Needed!",
            "title" : "Pipeline for $PR needs a manual approval.",
            "text": "• The pipeline flagged some infrastructure that could potentially be destroyed or recreated.\n• Please check step <${BUILD_URL}|*${STEP}*> to confirm if this is as intended.\n• If it was, release the on hold step \`${APPROVAL_STEP}\` when ready.",
            "emoji": true,
            "color": "#ffff00"
        }
    ]
}
EOF
}

if [[ -z "$PR" ]]
then
    PR="path_to_live"
else
    PR="Pull request ${PR}"
fi
echo "$(generate_payload)"

curl -X POST  ${WEBHOOK}  \
-H "Expect:" \
-H "Content-Type: application/json; charset=utf-8" \
--data "$(generate_payload)"
