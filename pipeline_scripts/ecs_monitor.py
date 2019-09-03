import urllib.request
import boto3
import argparse
import json
import os


class ECSMonitor:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecs_client = ''
    aws_ecs_cluster = ''

    def __init__(self, config_file):
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()
        self.aws_ecs_client = boto3.client(
            'ecs',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_account_id = parameters['account_id']
            self.aws_ecs_cluster = parameters['cluster_name']

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
            RoleSessionName='checking_ecs_task',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_task_status(self):
        try:
            print("Checking for services to settle...")
            waiter = self.aws_ecs_client.get_waiter('services_stable')
            waiter.wait(
                cluster=self.aws_ecs_cluster,
                services=[
                    'admin', 'api', 'front', 'pdf',
                ],
                WaiterConfig={
                    'Delay': 6,
                    'MaxAttempts': 99
                }
            )
        except:
            print("Exceeded attempts checking for task status")
            exit(1)
        else:
            print("ECS services stable")


def main():
    parser = argparse.ArgumentParser(
        description="Wait for services in an ECS cluster to become stable.")

    parser.add_argument("config_file_path", nargs='?', default="/tmp/environment_pipeline_tasks_config.json", type=str,
                        help="Path to config file produced by terraform")

    args = parser.parse_args()

    work = ECSMonitor(args.config_file_path)
    work.get_task_status()


if __name__ == "__main__":
    main()
