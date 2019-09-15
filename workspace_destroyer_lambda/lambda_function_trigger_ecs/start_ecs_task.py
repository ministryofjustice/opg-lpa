import urllib.request
import boto3
import argparse
import json
import os


class ECSMonitor:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecs_client = ''
    aws_ecs_cluster_arn = ''
    aws_ecs_task_definition_arn = ''

    def __init__(self):
        self.set_iam_role_session()
        self.aws_ecs_cluster_arn = os.getenv('AWS_ECS_CLUSTER_ARN')
        self.aws_ecs_task_definition_arn = os.getenv(
            'AWS_ECS_TASK_DEFINITION_ARN')
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
            self.aws_ecs_cluster_arn = parameters['cluster_name']

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/opg-lpa-ci'.format(
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
            RoleSessionName='starting_workspace_destroyer_ecs_task',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_task_status(self):
        try:
            print("Starting Workspace Storage ECS task...")
            waiter = self.start_task(
                cluster='string',
                taskDefinition='string'
            )
        except:
            print("Failed to start service")
            exit(1)


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
