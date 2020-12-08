import boto3
import json
#from requests_aws4auth import AWS4Auth
#import requests
import os

class S3Monitor:
    def set_iam_role_session(self):
        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )

        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::050256574573:role/opg-lpa-ci'
        else:
            role_arn = 'arn:aws:iam::050256574573:role/operator'

        print("about to assume role")
        result = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='session1',
        )
        print(result)

        self.s3Client = boto3.client('s3')
            #'version'     => 'latest',
            #'region'      => 'eu-west-1',
            #'credentials' =>  [
                #'key'    => result['Credentials']['AccessKeyId'],
                #'secret' => result['Credentials']['SecretAccessKey'],
                #'token'  => result['Credentials']['SessionToken']

    def monitor_bucket(self):
        # TODO to start just list bucket contents or just print out role or something

        print("about to get bucket contents")

        for key in self.s3Client.list_objects(Bucket='opg-lpa-casper-mailbox')['Contents']:
            print(key['Key'])


def main():
    monitor = S3Monitor()
    monitor.set_iam_role_session()
    monitor.monitor_bucket()


if __name__ == "__main__":
    main()

