#!/usr/bin/env sh
set -euox pipefail
WEBHOOK=$1
echo "notifying slack..."
curl -v POST -H 'Content-type application/json' ${WEBHOOK}  --data-binary @- << EOF
"{
    \"attachments\" :[
        {
            \"blocks\" : [],
            \"title\" : \":sign-warning: Approval Needed!\",
            \"text\": \"Pipeline for ${CIRCLE_PULL_REQUEST} needs approval, as some infra may be destroyed / recreated. go to ${CIRCLE_BUILD_URL}\",
            \"color\":\"#ffff00\"
        }
    ]
}"
EOF
