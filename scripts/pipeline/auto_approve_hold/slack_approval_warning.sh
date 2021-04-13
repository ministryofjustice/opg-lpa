#!/usr/bin/env sh
set -euox pipefail
WEBHOOK=$1
PR=$2
STEP=$3
BUILD_URL=$4

echo "notifying slack..."

generate_payload()
{
    cat <<EOF
{
    "attachments" :
    [
        {
            "blocks" : [],
            "title" : ":sign-warning: Approval Needed!",
            "text": "Pipeline for $PR needs a manual approval, as some infra may be destroyed / recreated.\n Please check build step $STEP on $BUILD_URL to confirm this is what was intended and approve if ok.",
            "color": "#ffff00",
            "emoji": true
        }
    ]
}
EOF
}
echo "$(generate_payload)"

curl -0 -v POST  ${WEBHOOK}  \
-H "Expect:" \
-H "Content-Type: application/json; charset=utf-8" \
--data "$(generate_payload)"
