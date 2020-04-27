import boto3
import argparse
import json
from requests_aws4auth import AWS4Auth
import requests
import os


class APIGatewayCaller:
    aws_account_id = ''
    api_gateway_url = ''
    aws_iam_role = ''
    aws_iam_session = ''
    aws_auth = ''

    def __init__(self):
        self.aws_account_id = os.getenv('AWS_ACCOUNT_ID')
        self.api_gateway_url = os.getenv('API_GATEWAY_URL')
        self.aws_iam_role = os.getenv('AWS_IAM_ROLE')
        self.set_iam_role_session()
        self.aws_auth = AWS4Auth(
            self.aws_iam_session['Credentials']['AccessKeyId'],
            self.aws_iam_session['Credentials']['SecretAccessKey'],
            'eu-west-1',
            'execute-api',
            session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def iterate_over_files(self):
        with open("uid_list") as file:
            for line in file:
                uid = line.rstrip()
                self.call_api_gateway(uid)

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/ci'.format(self.aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{0}:role/{1}'.format(
                self.aws_account_id, self.aws_iam_role)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='calling_api_gateway',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def call_api_gateway(self, lpa_id):
        method = 'GET'
        headers = {}
        body = ''
        url = str(self.api_gateway_url+lpa_id)
        response = requests.request(
            method, url, auth=self.aws_auth, data=body, headers=headers)
        print(response.text)


def main():
    parser = argparse.ArgumentParser(
        description="Look up LPA IDs on the Sirius API Gateway.")

    parser.add_argument("lpa_id", type=str,
                        help="LPA ID to look up in API Gateway")

    args = parser.parse_args()
    work = APIGatewayCaller()
    # work.iterate_over_files()
    work.call_api_gateway(args.lpa_id)


if __name__ == "__main__":
    main()
