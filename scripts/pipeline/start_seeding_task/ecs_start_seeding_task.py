import urllib.request
import boto3
import argparse
import json
import os
import sys


class ECSMonitor:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecs_client = ''
    aws_ecs_cluster = ''
    aws_ec2_client = ''
    aws_logs_client = ''
    aws_private_subnets = []
    db_client_security_group = ''
    seeding_security_group = ''
    environment = ''
    seeding_task_definition = ''
    seeding_task = ''
    nextForwardToken = ''
    logStreamName = ''

    def __init__(self, config_file):
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()

        self.aws_ecs_client = boto3.client(
            'ecs',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])
        self.aws_ec2_client = boto3.client(
            'ec2',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])
        self.aws_logs_client = boto3.client(
            'logs',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

        self.get_seeding_task_definition()
        self.get_subnet_id()

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_account_id = parameters['account_id']
            self.aws_ecs_cluster = parameters['cluster_name']
            self.environment = parameters['environment']
            self.db_client_security_group = parameters['db_client_security_group_id']
            self.seeding_security_group = parameters['seeding_security_group_id']

    def get_seeding_task_definition(self):
        # get the latest task definition for seeding
        # returns task defintion arn
        self.seeding_task_definition = self.aws_ecs_client.list_task_definitions(
            familyPrefix='{}-seeding'.format(self.environment),
            status='ACTIVE',
            sort='DESC',
            maxResults=1
        )['taskDefinitionArns'][0]
        print(self.seeding_task_definition)

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
            RoleSessionName='starting_seeding_ecs_task',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_subnet_id(self):
        # get ids for private subnets
        # returns a list of private subnet ids
        subnets = self.aws_ec2_client.describe_subnets(
            Filters=[
                {
                    'Name': 'tag:Name',
                    'Values': [
                        'private',
                    ]
                },
            ],
            MaxResults=5
        )
        for subnet in subnets['Subnets']:
            self.aws_private_subnets.append(subnet['SubnetId'])

    def run_seeding_task(self):
        # run a seeding task in ecs with a network configuration
        # sets a task arn for the seeding task started
        print("starting seeding task...")
        running_tasks = self.aws_ecs_client.run_task(
            cluster=self.aws_ecs_cluster,
            taskDefinition=self.seeding_task_definition,
            count=1,
            launchType='FARGATE',
            networkConfiguration={
                'awsvpcConfiguration': {
                    'subnets': self.aws_private_subnets,
                    'securityGroups': [
                        self.db_client_security_group,
                        self.seeding_security_group,
                    ],
                    'assignPublicIp': 'DISABLED'
                }
            },
        )
        self.seeding_task = running_tasks['tasks'][0]['taskArn']
        print(self.seeding_task)

    def check_task_status(self):
        # returns the status of the seeding task
        return self._get_task()['lastStatus']

    def get_task_exit_code(self):
        # returns the exit code of the task
        return self._get_task()['containers'][0]['exitCode']

    def _get_task(self):
        # returns the status of the seeding task
        return self.aws_ecs_client.describe_tasks(
            cluster=self.aws_ecs_cluster,
            tasks=[
                self.seeding_task,
            ]
        )['tasks'][0]

    def wait_for_task_to_start(self):
        # wait for the seeding task to start
        print("waiting for seeding task to start...")
        waiter = self.aws_ecs_client.get_waiter('tasks_running')
        waiter.wait(
            cluster=self.aws_ecs_cluster,
            tasks=[
                self.seeding_task,
            ],
            WaiterConfig={
                'Delay': 10,
                'MaxAttempts': 100
            }
        )

    def get_logs(self):
        # retrieve logstreeam for the seeding task started
        # formats and prints simple log output
        log_events = self.aws_logs_client.get_log_events(
            logGroupName=f"{self.environment}_application_logs",
            logStreamName=self.logStreamName,
            nextToken=self.nextForwardToken,
            startFromHead=False
        )
        for event in log_events['events']:
            print(f"timestamp: {event['timestamp']}: message: {event['message']}")
        self.nextForwardToken = log_events['nextForwardToken']

    def print_task_logs(self):
        # lifecycle for getting log streams
        # get logs while task is running
        # after task finishes, print remaining logs
        seeding_task_split = self.seeding_task.rsplit('/', 1)[-1]
        self.logStreamName = f"{self.environment}.seeding.online-lpa/app/{seeding_task_split}"

        print(f"Streaming logs for logstream: {self.logStreamName}")

        self.nextForwardToken = 'f/0'

        while self.check_task_status() == "RUNNING":
            self.get_logs()

        self.get_logs()
        print("task stopped running")


def main():
    parser = argparse.ArgumentParser(
        description="Start the seeding task for the Make an LPA database")

    parser.add_argument("config_file_path", nargs='?', default="/tmp/environment_pipeline_tasks_config.json", type=str,
                        help="Path to config file produced by terraform")

    args = parser.parse_args()

    work = ECSMonitor(args.config_file_path)
    work.run_seeding_task()
    work.wait_for_task_to_start()
    work.print_task_logs()

    # at this point, the task has finished: see print_task_logs() where
    # we check for this

    # get the task exit code and use this as the exit code for this script
    return work.get_task_exit_code()

if __name__ == "__main__":
    sys.exit(main())
