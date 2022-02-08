import urllib.request
import boto3
import json
import os

class ECSMonitor:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecs_client = ''
    aws_ecs_cluster = ''
    aws_ec2_client = ''
    aws_logs_client = ''
    aws_private_subnets = []
    db_client_security_group = ''
    security_group = ''
    environment = ''
    task_definition = ''
    task = ''
    nextForwardToken = ''
    logStreamName = ''
    taskName = ''

    def __init__(self, config_file, nameOfTask):
        self.taskName = nameOfTask
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

        self.get_task_definition()
        self.get_subnet_id()

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_account_id = parameters['account_id']
            self.aws_ecs_cluster = parameters['cluster_name']
            self.environment = parameters['environment']
            self.db_client_security_group = parameters['db_client_security_group_id']
            self.security_group = parameters[f"{self.taskName}_security_group_id"]

    def get_task_definition(self):
        # get the latest task definition 
        # returns task defintion arn
        self.task_definition = self.aws_ecs_client.list_task_definitions(
            familyPrefix=f"{self.environment}-{self.taskName}",
            status='ACTIVE',
            sort='DESC',
            maxResults=1
        )['taskDefinitionArns'][0]
        print(self.task_definition)

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
            RoleSessionName=f"starting_{self.taskName}_ecs_task",
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

    def run_task(self):
        # run a task in ecs with a network configuration
        # sets a task arn for the task started
        print(f"starting {self.taskName} task...")
        running_tasks = self.aws_ecs_client.run_task(
            cluster=self.aws_ecs_cluster,
            taskDefinition=self.task_definition,
            count=1,
            launchType='FARGATE',
            networkConfiguration={
                'awsvpcConfiguration': {
                    'subnets': self.aws_private_subnets,
                    'securityGroups': [
                        self.db_client_security_group,
                        self.security_group,
                    ],
                    'assignPublicIp': 'DISABLED'
                }
            },
        )
        self.task = running_tasks['tasks'][0]['taskArn']
        print(self.task)

    def check_task_status(self):
        # returns the status of the task
        return self._get_task()['lastStatus']

    def get_task_exit_code(self):
        # returns the exit code of the task
        return self._get_task()['containers'][0]['exitCode']

    def _get_task(self):
        # returns the status of the task
        return self.aws_ecs_client.describe_tasks(
            cluster=self.aws_ecs_cluster,
            tasks=[
                self.task,
            ]
        )['tasks'][0]

    def wait_for_task_to_start(self):
        # wait for the task to start
        print(f"waiting for {self.taskName} task to start...")
        waiter = self.aws_ecs_client.get_waiter('tasks_running')
        waiter.wait(
            cluster=self.aws_ecs_cluster,
            tasks=[
                self.task,
            ],
            WaiterConfig={
                'Delay': 10,
                'MaxAttempts': 100
            }
        )

    def get_logs(self):
        # retrieve logstreeam for the task started
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
        task_split = self.task.rsplit('/', 1)[-1]
        self.logStreamName = f"{self.environment}.{self.taskName}.online-lpa/app/{task_split}"

        print(f"Streaming logs for logstream: {self.logStreamName}")

        self.nextForwardToken = 'f/0'

        while self.check_task_status() == "RUNNING":
            self.get_logs()

        self.get_logs()
        print("task stopped running")
