#! /bin/bash
generate_post_environment_domains()
{
    cat <<EOF
{
    "blocks": [],
    "attachments": [
    {
        "color": "#508c18",
        "blocks":
        [
            {
                "type": "header",
                "text": {
                "type": "plain_text",
                "text": "Online LPA Development Environment Ready",
                "emoji": false
                }
            },
            {
                "type": "section",
                "text": {
                "type": "mrkdwn",
                "text": "public facing url: <https://${PUBLIC_FACING_DOMAIN}/home>"
                }
            },
            {
                "type": "section",
                "text": {
                "type": "mrkdwn",
                "text": "front url: <https://${FRONT_DOMAIN}>"
                }
            },
            {
                "type": "section",
                "text": {
                "type": "mrkdwn",
                "text": "admin url: <https://${ADMIN_DOMAIN}>"
                }
            },
            {
                "type": "context",
                "elements":
                [
                    {
                        "type": "plain_text",
                        "text": "by user: ${CIRCLE_USERNAME} - branch: ${CIRCLE_BRANCH} - Commit Message: ${COMMIT_MESSAGE//$'\n'/\\n}"
                    }
                ]
            }
        ]
    }
    ]
}
EOF
}

generate_post_environment_domains > /tmp/post_environment_domains.json

echo message sent:
cat /tmp/post_environment_domains.json

echo 'export SLACK_POST_ENVIRONMENT_DOMAIN_TEMPLATE=$(cat /tmp/post_environment_domains.json)' >> $BASH_ENV
