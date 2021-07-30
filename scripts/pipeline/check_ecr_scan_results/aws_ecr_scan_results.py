import argparse
import json
import os
import traceback

import boto3
import jinja2
from jinja2.loaders import FileSystemLoader
from slack_sdk import WebClient


class ECRScanChecker:

    aws_account_id = ''
    aws_iam_session = ''
    aws_ecr_client = ''
    images_to_check = []
    tag = ''
    report = ''
    result_limit = ''
    test_mode = False
    slack_channel = ''
    slack_token = ''
    search_terms = []
    branch = ''
    build_url = ''

    def __init__(self, args):
        self.search_terms = [s.strip() for s in args.search.split(",")]
        self.result_limit = int(args.result_limit)
        self.aws_account_id = args.account_id
        self.test_mode = args.test_mode
        self.slack_channel = args.slack_channel
        self.slack_token =  args.slack_token
        self.tag = args.tag
        self.branch = args.branch
        self.build_url = args.build_url

        self.template_environment = jinja2.Environment(
            loader=FileSystemLoader("templates"))

        self.set_iam_role_session()
        self.set_ecr_client()
        self.images_to_check = self.get_repositories(self.search_terms)

    def render(self, template_file, template_vars):
        tmpl = self.template_environment.get_template(template_file)
        return tmpl.render(**template_vars)

    def get_severity_emoji(self, severity):
        if severity == "CRITICAL":
            return "siren"
        if severity == "HIGH":
            return "x"
        if severity == "MEDIUM":
            return "sign-warning"
        if severity == "LOW":
            return "information_source"
        return "grey_question"

    def set_ecr_client(self):
        self.aws_ecr_client = boto3.client(
            'ecr',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken']
            )

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = f"arn:aws:iam::{self.aws_account_id}:role/opg-lpa-ci"
        else:
            role_arn = f"arn:aws:iam::{self.aws_account_id}:role/operator"

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

    def get_repositories(self, search_terms):
        images_to_check = []
        response = self.aws_ecr_client.describe_repositories()
        for repository in response["repositories"]:
            for search_term in search_terms:
                if search_term != None and search_term in repository["repositoryName"]:
                    images_to_check.append(repository["repositoryName"])
        return images_to_check

    def wait_for_scan_completion(self, image, tag):
        try:
            waiter = self.aws_ecr_client.get_waiter('image_scan_complete')
            waiter.wait(
                repositoryName=image,
                imageId={
                    'imageTag': tag
                },
                WaiterConfig={
                    'Delay': 5,
                    'MaxAttempts': 60
                }
            )
        except:
            print(f"No ECR image scan results for image {image}, tag {tag}")

    def get_ecr_scan_findings(self, image, tag):
        response = self.aws_ecr_client.describe_image_scan_findings(
            repositoryName=image,
            imageId={
                'imageTag': tag
            },
            maxResults=self.result_limit
        )
        return response

    def wait_for_scans(self):
        print("Waiting for ECR scans to complete...")
        for image in self.images_to_check:
            self.wait_for_scan_completion(image, self.tag)
        print("ECR image scans complete")

    def generate_report(self):
        print("Checking ECR scan results...")
        for image in self.images_to_check:
            try:
                findings = self.get_ecr_scan_findings(
                    image, self.tag)["imageScanFindings"]

                if findings["findings"] != []:
                    counts = findings["findingSeverityCounts"]
                    titleInfo = {
                        "image": image,
                        "counts": counts,
                        "report_limit": self.result_limit
                    }


                    self.report = self.render("header.j2", titleInfo)
                    findingResults = []

                    for finding in findings["findings"]:
                        findingResult = {
                            "cve_tag": finding["name"],
                            "emoji": self.get_severity_emoji(finding["severity"]),
                            "severity": finding["severity"],
                            "cve_link": finding["uri"],
                            "image": image,
                            "tag": self.tag
                        }
                        findingResult["description"] = "None"

                        if "description" in finding:
                            findingResult["description"] = finding["description"]
                        findingResults.append(findingResult)

                    self.report += self.render("finding.j2", {"results": findingResults})

            except Exception as e:
                print(
                    f"Unable to get ECR image scan results for image {image}, tag {self.tag}")
                print(f"The following error occurred:{e}")
                print('trace:')
                traceback.print_exc()

        if not self.report:
            self.report = self.render("no_scan_results.j2", {"image": image})

        self.write_build_details()


    def write_build_details(self):
        build_vars = {
           "branch": self.branch,
            "build_url": self.build_url
        }

        self.report += self.render("footer.j2", build_vars)


    def post_to_slack(self):
        message_content = {"blocks": self.report}

        message = json.loads(self.render("message.j2", message_content))

        if self.test_mode:
            print("TEST MODE - not sending to slack.")
            print(message)
        else:
            print("sending slack message...")
            client = WebClient(token=self.slack_token)
            client.chat_postMessage(channel=self.slack_channel,
                                    blocks=message['blocks'])


def main():
    parser = argparse.ArgumentParser(
        description="Check ECR Scan results for all service container images.")
    parser.add_argument("--search",
                        default="",
                        help="a comma separated list of repository names to partial match:"
                        "for example online-lpa,perfplat")
    parser.add_argument("--tag",
                        default="latest",
                        help="Image tag to check scan results for.")
    parser.add_argument("--result_limit",
                        default=5,
                        help="How many results for each image to return. Defaults to 5")
    parser.add_argument("--slack_token",
                        help="slack api token to use")
    parser.add_argument("--slack_channel",
                        help="slack channel to post to")
    parser.add_argument("--test_mode",
                        help="run the script in test mode, does not post to slack",
                        dest="test_mode",
                        action="store_true")
    parser.add_argument("--account_id",
                        default="311462405659",
                        help="Optionally specify account for scan, defauts to the management account")
    parser.add_argument("--branch",
                        default="N/A",
                        help="branch for build")
    parser.add_argument("--build_url",
                        default="N/A",
                        help="build tool URL")

    args = parser.parse_args()


    work = ECRScanChecker(args)
    work.wait_for_scans()
    work.generate_report()
    work.post_to_slack()


if __name__ == "__main__":
    main()
