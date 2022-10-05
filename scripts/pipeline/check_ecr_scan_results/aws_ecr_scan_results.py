import argparse
import datetime
import json
import os
import sys
import traceback
from datetime import date, datetime

import boto3
import boto3.exceptions
import botocore
import jinja2
from jinja2.loaders import FileSystemLoader
from slack_sdk import WebClient


class ECRScanChecker:
    aws_iam_session = ""
    aws_ecr_client = ""
    aws_inspector2_client = ""
    aws_account_id = ""

    def __init__(self, account_id):

        self.template_environment = jinja2.Environment(
            loader=FileSystemLoader("templates")
        )
        self.aws_account_id = account_id
        self.set_iam_role_session()
        self.set_ecr_client()
        self.set_aws_inspector2_client()

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

    @staticmethod
    def get_aws_client(client_type, aws_iam_session, region="eu-west-1"):
        client = boto3.client(
            client_type,
            region_name=region,
            aws_access_key_id=aws_iam_session["Credentials"]["AccessKeyId"],
            aws_secret_access_key=aws_iam_session["Credentials"]["SecretAccessKey"],
            aws_session_token=aws_iam_session["Credentials"]["SessionToken"],
        )
        return client

    def set_ecr_client(self):
        self.aws_ecr_client = self.get_aws_client("ecr", self.aws_iam_session)

    def set_aws_inspector2_client(self):
        self.aws_inspector2_client = self.get_aws_client(
            "inspector2", self.aws_iam_session
        )

    def set_iam_role_session(self):
        role_arn = ""
        if os.getenv("CI"):
            role_arn = f"arn:aws:iam::{self.aws_account_id}:role/opg-lpa-ci"
        else:
            role_arn = f"arn:aws:iam::{self.aws_account_id}:role/operator"

        sts = boto3.client("sts", region_name="eu-west-1")

        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName="checking_ecr_image_scan",
            DurationSeconds=900,
        )
        self.aws_iam_session = session

    def get_repositories(self, search):
        search_terms = [s.strip() for s in search.split(",")]
        repos_to_check = []
        response = self.aws_ecr_client.describe_repositories()
        for repository in response["repositories"]:
            for search_term in search_terms:
                if (
                    search_term is not None
                    and search_term in repository["repositoryName"]
                ):
                    repos_to_check.append(repository["repositoryName"])
        return repos_to_check

    def get_ecr_scan_findings(self, image, tag, result_limit):
        response = self.aws_ecr_client.describe_image_scan_findings(
            repositoryName=image, imageId={"imageTag": tag}, maxResults=result_limit
        )
        return response

    def generate_report(
        self,
        repositories,
        tag,
        push_date,
        report_limit,
        branch,
        build_url,
        slack_channel,
        slack_token,
        test_mode,
    ):
        print("Checking ECR scan results...")

        for image in repositories:
            print(f"image: {image}")
            report = ""
            try:
                findings = self.list_findings(
                    image, tag, push_date, self.aws_account_id, report_limit
                )
                if findings["findings"] != []:
                    title_info = {"image": image, "report_limit": report_limit}

                    report = self.render("header.j2", title_info)
                    finding_results = []

                    for finding in findings["findings"]:
                        finding_result = self.summarise_finding(finding, tag, image)
                        finding_results.append(finding_result)
                    report += self.render("finding.j2", {"results": finding_results})
                    report += self.write_build_details(branch, build_url)
                    self.post_to_slack(report, slack_channel, slack_token, test_mode)
            except self.aws_ecr_client.exceptions.ImageNotFoundException:
                print(
                    f"skipping finding check for image: {image} tag: {tag} - no image present"
                )
                continue

            except botocore.exceptions.ClientError as error:
                print(
                    f"Unable to get ECR image scan results for image {image}, tag {tag}"
                )
                print(f"The following error occurred:{error}")
                print(
                    error.response["Error"]["Code"], error.response["Error"]["Message"]
                )
                print("trace:")
                traceback.print_exc()
                sys.exit(1)

    def list_findings(
        self, repository_name, tag, push_date, aws_account_id, report_limit
    ):
        date_start_inclusive = datetime.combine(push_date, datetime.min.time())

        date_end_inclusive = datetime.combine(push_date, datetime.max.time())

        response = self.aws_inspector2_client.list_findings(
            filterCriteria={
                "awsAccountId": [
                    {"comparison": "EQUALS", "value": str(aws_account_id)},
                ],
                "ecrImagePushedAt": [
                    {
                        "endInclusive": date_end_inclusive,
                        "startInclusive": date_start_inclusive,
                    },
                ],
                "ecrImageRepositoryName": [
                    {"comparison": "EQUALS", "value": repository_name},
                ],
                "ecrImageTags": [
                    {"comparison": "EQUALS", "value": tag},
                ],
            },
            maxResults=report_limit,
            sortCriteria={"field": "SEVERITY", "sortOrder": "DESC"},
        )
        return response

    def summarise_finding(self, finding, tag, image):
        finding_result = {
            "title": finding["title"],
            "emoji": self.get_severity_emoji(finding["severity"]),
            "severity": finding["severity"],
            "image": image,
            "tag": tag,
            "type": finding["type"],
        }

        finding_result["description"] = "None"

        if "description" in finding:
            # make json string,strip start and end quotes
            finding_result["description"] = json.dumps(finding["description"])[1:-1]
        return finding_result

    def write_build_details(self, branch, build_url):
        build_vars = {"branch": branch, "build_url": build_url}

        return self.render("footer.j2", build_vars)

    def post_to_slack(self, report, slack_channel, slack_token, test_mode):
        message_content = {"blocks": report}
        message = json.loads(self.render("message.j2", message_content))

        if test_mode:
            print("TEST MODE - not sending to slack.")
            print(message)
        else:
            print("sending slack message...")
            client = WebClient(token=slack_token)
            client.chat_postMessage(
                channel=slack_channel,
                blocks=message["blocks"],
                text="Scan Results Received",
            )


def main():
    parser = argparse.ArgumentParser(
        description="Check ECR Scan results for all service container images."
    )
    parser.add_argument(
        "--search",
        default="",
        help="a comma separated list of repository names to partial match:"
        "for example online-lpa,perfplat",
    )
    parser.add_argument(
        "--tag", default="latest", help="Image tag to check scan results for."
    )
    parser.add_argument(
        "--result_limit",
        default=5,
        help="How many results for each image to return. Defaults to 5",
    )
    parser.add_argument("--slack_token", help="slack api token to use")
    parser.add_argument("--slack_channel", help="slack channel to post to")
    parser.add_argument(
        "--test_mode",
        help="run   e script in test mode, does not post to slack",
        dest="test_mode",
        action="store_true",
    )
    parser.add_argument(
        "--account_id",
        default="311462405659",
        help="Optionally specify account for scan, defaults to the management account",
    )
    parser.add_argument("--branch", default="N/A", help="branch for build")
    parser.add_argument("--build_url", default="N/A", help="build tool URL")
    parser.add_argument(
        "--ecr_push_date",
        default=date.today(),
        help="ECR Image push datetime in format YYYY-MM-dd",
    )

    args = parser.parse_args()

    work = ECRScanChecker(args.account_id)

    repos_to_check = work.get_repositories(args.search)

    work.generate_report(
        repos_to_check,
        args.tag,
        args.ecr_push_date,
        args.result_limit,
        args.branch,
        args.build_url,
        args.slack_channel,
        args.slack_token,
        args.test_mode,
    )


if __name__ == "__main__":
    main()
