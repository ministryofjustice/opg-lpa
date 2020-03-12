import boto3
import argparse
import json
import os
import sys
import requests


class ECRScanChecker:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecr_client = ''
    aws_ecr_repository_path = ''
    images_to_check = []
    report = ''

    def __init__(self, config_file):
        self.images_to_check = [
            "front_web",
            "front_app",
            "api_web",
            "api_app",
            "pdf_app",
            "admin_web",
            "admin_app",
            "seeding_app"
        ]
        self.aws_ecr_repository_path = 'online-lpa/'
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()
        self.aws_ecr_client = boto3.client(
            'ecr',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_account_id = 311462405659

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/ci'.format(
                self.aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{}:role/operator'.format(
                self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='checking_ecr_image_scan',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def recursive_wait(self, tag):
        print("Waiting for ECR scans to complete...")
        for image in self.images_to_check:
            self.wait_for_scan_completion(image, tag)
        print("ECR image scans complete")

    def wait_for_scan_completion(self, image, tag):
        try:
            repository_name = self.aws_ecr_repository_path+image
            waiter = self.aws_ecr_client.get_waiter('image_scan_complete')
            waiter.wait(
                repositoryName=repository_name,
                imageId={
                    'imageTag': tag
                },
                maxResults=1,
                WaiterConfig={
                    'Delay': 5,
                    'MaxAttempts': 60
                }
            )
        except:
            print("Unable to return ECR image scan results for", tag)
            exit(1)

    def recursive_check_make_report(self, tag):
        print("Checking ECR scan results...")
        for image in self.images_to_check:
            if self.get_ecr_scan_findings(image, tag)[
                    "imageScanFindings"]["findings"] != []:
                cve = self.get_ecr_scan_findings(image, tag)[
                    "imageScanFindings"]["findings"][0]["name"]
                description = self.get_ecr_scan_findings(image, tag)[
                    "imageScanFindings"]["findings"][0]["description"]
                severity = self.get_ecr_scan_findings(image, tag)[
                    "imageScanFindings"]["findings"][0]["severity"]
                link = self.get_ecr_scan_findings(image, tag)[
                    "imageScanFindings"]["findings"][0]["uri"]
                title = "\n\n:warning: *AWS ECR Scan found results for {}:* \n\n".format(
                    self.aws_ecr_repository_path+image)
                result = "*Image:* {0} \n*Severity:* {1} \n*CVE:* {2} \n*Description:* {3} \n*Link:* {4}\n\n".format(
                    self.aws_ecr_repository_path+image, severity, cve, description, link)
                self.report += title + result
                print(self.report)

    def get_ecr_scan_findings(self, image, tag):
        repository_name = self.aws_ecr_repository_path+image
        response = self.aws_ecr_client.describe_image_scan_findings(
            repositoryName=repository_name,
            imageId={
                'imageTag': tag
            },
            maxResults=1
        )
        return response

    def post_to_slack(self, slack_webhook):
        if os.getenv('CI'):
            ci_footer = "*Github Branch:* {0}\n\n*CircleCI Job Link:* {1}\n\n".format(
                os.getenv('CIRCLE_BRANCH'),
                os.getenv('CIRCLE_BUILD_URL'))
            self.report += ci_footer

        post_data = json.dumps({"text": self.report})
        response = requests.post(
            slack_webhook, data=post_data,
            headers={'Content-Type': 'application/json'}
        )
        if response.status_code != 200:
            raise ValueError(
                'Request to slack returned an error %s, the response is:\n%s'
                % (response.status_code, response.text)
            )


def main():
    parser = argparse.ArgumentParser(
        description="Check ECR Scan results for all service container images.")

    parser.add_argument("--config_file_path",
                        nargs='?',
                        default="/tmp/cluster_config.json",
                        type=str,
                        help="Path to config file produced by terraform")
    parser.add_argument("--tag",
                        default="latest",
                        help="Image tag to check scan results for.")
    parser.add_argument("--slack_webhook",
                        default=os.getenv('SLACK_WEBHOOK'),
                        help="Webhook to use, determines what channel to post to")
    parser.add_argument("--post_to_slack",
                        default=True,
                        help="Optionally turn off posting messages to slack")

    args = parser.parse_args()
    work = ECRScanChecker(args.config_file_path)
    work.recursive_wait(args.tag)
    work.recursive_check_make_report(args.tag)
    if args.slack_webhook is None:
        print("No slack webhook provided, skipping post of results to slack")
    if args.post_to_slack == True and args.slack_webhook is not None:
        work.post_to_slack(args.slack_webhook)


if __name__ == "__main__":
    main()
