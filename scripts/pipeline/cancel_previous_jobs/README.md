# Cancel Redundant CircleCI Builds

This script will cancel redundant builds when new ones are started. If jobs matching the `terms_to_waitfor` argument are running, the script will wait for them to end first, then cancel the build.

## Run script

The script uses a CircleCI Personal API Token to authorise the requests against the API. Information about creating a token can be found at <https://circleci.com/docs/2.0/managing-api-tokens/>.

The token should be added as an Environment Variable to the CircleCI project settings. Information about setting Environment Variables can be found at <https://circleci.com/docs/2.0/env-vars/#setting-an-environment-variable-in-a-shell-command/>.

All arguments are required, and are available in CircleCI as built-in Environment Variables. <https://circleci.com/docs/2.0/env-vars/#built-in-environment-variables>

Example command to run script.

``` bash
python scripts/pipeline/cancel_previous_jobs/cancel_redundant_builds.py \
--circle_project_username ${CIRCLE_PROJECT_USERNAME} \
--circle_project_reponame ${CIRCLE_PROJECT_REPONAME} \
--circle_branch ${CIRCLE_BRANCH} \
--circle_builds_token ${CIRCLECI_API_KEY} \
--terms_to_waitfor "dev_account_apply_terraform,dev_environment_apply_terraform,apply_email_terraform"
```
