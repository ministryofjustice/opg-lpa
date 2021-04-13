!/usr/bin/env sh
set -euo pipefail
SLACK_DEV_WEBHOOK=$1
echo "notifying slack..."
curl -X POST -H 'Content-type application/json' ${SLACK_DEV_WEBHOOK}  --data  <<EOF
'{
    attachments :
    [
        "pretext" : "Approval Needed!",
        "fallback": "Pipeline for ${CIRCLE_PULL_REQUEST} needs approval, as some infra may be destroyed / recreated. go to ${CIRCLE_BUILD_URL}",
        "color":"ffff00"
    ]
}'
EOF
