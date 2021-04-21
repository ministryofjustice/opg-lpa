#! /bin/bash
generate_slack_notify_production_release()
{
    cat <<EOF
{
    "blocks": [],
    "attachments":
    [
        {
            "color": "#508c18",
            "blocks":
            [
                {
                    "type": "header",
                    "text":
                    {
                        "type": "plain_text",
                        "text": "Production Release Successful",
                        "emoji": false
                    }
                },
                {
                    "type": "section",
                    "text":
                    {
                        "type": "mrkdwn",
                        "text": "public facing url: <https://${PUBLIC_FACING_DOMAIN}/home>"
                    }
                },
                {
                    "type": "section",
                    "text":
                    {
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

generate_slack_notify_production_release > /tmp/slack_notify_production_release.json

echo message sent:
cat /tmp/slack_notify_production_release.json

echo 'export SLACK_NOTIFY_PRODUCTION_RELEASE_TEMPLATE=$(cat /tmp/slack_notify_production_release.json)' >> $BASH_ENV
