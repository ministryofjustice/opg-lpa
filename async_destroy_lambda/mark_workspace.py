import boto3
import os
import json
import argparse


class QueueWriter:
    aws_account_id = ''
    aws_iam_session = ''
    aws_sqs_client = ''
    workspace_destory_queue_url = ''
    workspace_name = ''

    def __init__(self, config_file, workspace_name):
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()
        self.workspace_name = workspace_name
        self.aws_sqs_client = boto3.client(
            'sqs',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_account_id = parameters['account_id']
            self.workspace_destory_queue_url = parameters['workspace_destory_queue_url']

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/ci'.format(self.aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{}:role/account-write'.format(
                self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='writing_message_to_workspace_destory_queue_url',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def write_workspace_to_queue(self):
        try:
            self.aws_sqs_client.send_message(
                QueueUrl=self.workspace_destory_queue_url,
                MessageBody=self.workspace_name,
            )
        except Exception as e:
            print(e)
            exit(1)


def main():
    protected_workspaces = ['default', 'preproduction', 'production']
    parser = argparse.ArgumentParser()
    parser.add_argument("config_file_path", type=str,
                        help="Path to config file produced by terraform")
    parser.add_argument("workspace_name", type=str,
                        help="Name of a Terraform workspace to destroy")

    args = parser.parse_args()
    work = QueueWriter(args.config_file_path, args.workspace_name)
    if not args.workspace_name in protected_workspaces:
        work.write_workspace_to_queue()
    else:
        print("Workspace {} is protected. Terraform destroy steps skipped").format(
            str(args.workspace_name))
        exit(0)


if __name__ == "__main__":
    main()
