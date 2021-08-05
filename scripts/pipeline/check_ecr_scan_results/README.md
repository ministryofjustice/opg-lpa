# Check AWS ECR scan results

This script returns ECR scan results for known vulnerabilities.

Information about ECR scan on image push is available at <https://docs.aws.amazon.com/AmazonECR/latest/userguide/image-scanning.html>

The script takes arguments for:

- Repository name search terms (comma separated) e.g, `online-lpa,perfplat`.
- Image tag (optional) default to `latest`.
- Slack token.
- Slack channel to use for posting results.
- Result limits (optional), default to 5.
- AWS Account id - defaults to the management account.
- Test mode flag (optional) - prints only to `stdout` if used.
- Branch name (optional).
- Build url (optional).

The script will return the results, in order of most severe first, for each image.

## Install python dependencies with pip

``` bash
pip install -r requirements.txt
```

## Run script

The script uses your IAM user credentials to assume the appropriate role.

This also assumes you have the slack token and channel to hand for posting to.
You can provide the script credentials using aws-vault:

minimal example, for performing a scan on online-lpa repo for the latest tag:

``` bash
aws-vault exec identity -- python3 aws_ecr_scan_results.py \
  --search online-lpa \
  --slack_token $SLACK_ACCESS_TOKEN \
  --slack_channel $BUILD_SLACK_CHANNEL
```

this is the full list of options and flags:

``` bash
aws-vault exec identity -- python3 aws_ecr_scan_results.py \
  --search online-lpa,perfplat-worker \
  --tag <image-tagged> \
  --slack_token $SLACK_ACCESS_TOKEN \
  --slack_channel $BUILD_SLACK_CHANNEL \
  --result_limit 10  \
  --test_mode \
  --account_id <aws_account_id>
```
