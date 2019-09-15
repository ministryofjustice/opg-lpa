import boto3
import os
import json
import argparse


class QueueReader:
    aws_account_id = ''
    aws_iam_session = ''
    aws_sqs_client = ''
    workspace_destory_queue_url = ''
    receipt_handle = ''
    workspace_name = ''

    def __init__(self, config_file):
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()
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
            role_arn = 'arn:aws:iam::{}:role/operator'.format(
                self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='getting_message_from_workspace_destory_queue_url',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_workspace_from_queue(self):
        try:
            response = self.aws_sqs_client.receive_message(
                QueueUrl=self.workspace_destory_queue_url,
                AttributeNames=[
                    'SentTimestamp'
                ],
                MaxNumberOfMessages=1,
                MessageAttributeNames=[
                    'All'
                ],
                VisibilityTimeout=0,
                WaitTimeSeconds=0
            )

            message = response['Messages'][0]
            self.receipt_handle = message['ReceiptHandle']
            self.workspace_name = message['Body']
            print(self.workspace_name)
        except:
            print("No messagees received/available")

    def watch_queue_for_messages(self):
        while True:
            messages = self.aws_sqs_client.receive_message(
                QueueUrl=self.workspace_destory_queue_url, MaxNumberOfMessages=10)
            if 'Messages' in messages:
                for message in messages['Messages']:
                    print(message['Body'])
                    self.aws_sqs_client.delete_message(
                        QueueUrl=self.workspace_destory_queue_url, ReceiptHandle=message['ReceiptHandle'])

    def delete_workspace_from_queue(self):
        try:
            self.aws_sqs_client.delete_message(
                QueueUrl=self.workspace_destory_queue_url,
                ReceiptHandle=self.receipt_handle
            )
        except:
            print("No messages available to delete")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("config_file_path", nargs='?', default="./workspace_detroyer_config.json", type=str,
                        help="Path to config file produced by terraform")

    args = parser.parse_args()
    work = QueueReader(args.config_file_path)
    work.get_workspace_from_queue()


if __name__ == "__main__":
    main()
