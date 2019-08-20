import os
from subprocess import Popen, PIPE, CalledProcessError
import wget
from zipfile import ZipFile
import stat
from git import Repo


def lambda_handler(event, context):
    for record in event['Records']:
        workspace = record["body"]
        print(workspace)
    return {
        'statusCode': 200,
        'body': json.dumps('Success')
    }
