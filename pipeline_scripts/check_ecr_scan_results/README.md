# Check AWS ECR scan results

This script returns ECR scan results for known vulnerabilities.

Information about ECR scan on push is available at <https://docs.aws.amazon.com/AmazonECR/latest/userguide/image-scanning.html>

The script takes arguments for image tag to return results for, the Slack webhook to use for posting results and whether to post to slack.

If omitted, the image tag will default to `latest`, and the webhook will default to the system environment variable `$SLACK_WEBHOOK`.

## Install python dependencies with pip

``` bash
pip install -r requirements.txt
```

## Run script

The script uses your IAM user credentials to assume the appropriate role.

You can provide the script credentials using aws-vault

``` bash
aws-vault exec identity -- python pipeline_scripts/check_ecr_scan_results/aws_ecr_scan_results.py \
  --tag latest \
```

to configure other options, use the additional arguments

``` bash
aws-vault exec identity -- python pipeline_scripts/check_ecr_scan_results/aws_ecr_scan_results.py \
  --config_file_path "\tmp\configfile.json" \
  --tag latest \
  --webhook "https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX" \
  --post_to_slack True
```
