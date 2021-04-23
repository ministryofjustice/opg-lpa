#! /bin/bash
    echo "COMMIT_MESSAGE:"
    echo ${COMMIT_MESSAGE}
    # needed as circleci did not santize the input for json properly.
    SANITISED_COMMIT_MESSAGE=$(echo "${COMMIT_MESSAGE}" | sed '/$'\n'/\\\n/g' | sed 's/"/\\""/g')
    echo "${SANITISED_COMMIT_MESSAGE}"
generate_post_environment_domains()
{
    # needed as circleci did not santize the input for json properly.
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
                            "type": "mrkdwn",
                            "text": "by user: ${CIRCLE_USERNAME} - branch: ${CIRCLE_BRANCH} - Commit Message: ${SANITISED_COMMIT_MESSAGE}"
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
