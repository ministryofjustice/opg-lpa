import boto3
import json
import os
import quopri
import time
import re

class S3Monitor:
    def set_iam_role_session(self):
        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )

        print("CI is")
        print(os.getenv('CI'))
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::050256574573:role/opg-lpa-ci'
        else:
            role_arn = 'arn:aws:iam::050256574573:role/operator'

        result = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='session1',
        )
        #print(result)

        self.s3Client = boto3.client('s3')

    def parseBody(self, bodyContent, subject, thetype, linkRegex):
        #print(bodyContent)
        #regex = '|(https:\/\/\S+' + linkRegex + '\/[a-zA-Z0-9]+)|sim'
        #regex = '(https:\/\/\S+' + linkRegex + '\/[a-zA-Z0-9]+)|sim'

        #regexstr = '|(https:\/\/\S+' + linkRegex + '\/[a-zA-Z0-9]+)|sim'
        #regex = re.compile(regexstr)


        regex = '|(https:\/\/\S+\/[a-zA-Z0-9]+)|sim'
        result = re.match(regex, bodyContent )
        print(result.group(0))
        #if re.match(regex, bodyContent, matches) > 0) {
        #    activationLink = matches[1];

    def monitor_bucket(self):
        seenkeys = []

        while True:
            for s3obj in self.s3Client.list_objects(Bucket='opg-lpa-casper-mailbox')['Contents']:
                s3Key = s3obj['Key']
                if not s3Key in seenkeys:
                    result = self.s3Client.get_object(Bucket='opg-lpa-casper-mailbox',Key=s3Key)
                    #bodyContent = quopri.decodestring(result["Body"].read())
                    bodyContent = quopri.decodestring(result["Body"].read()).decode('latin-1')
                    #print(bodyContent)
                    self.parseBody(bodyContent, 'Activate your lasting power of attorney account', 'activation', 'signup\/confirm')
                    self.parseBody(bodyContent, 'Password reset request', 'passwordreset', 'forgot-password\/reset')
                    seenkeys.append(s3Key)
                else:
                    print("already seen " + s3Key)
            time.sleep(5)


def main():
    monitor = S3Monitor()
    monitor.set_iam_role_session()
    monitor.monitor_bucket()


if __name__ == "__main__":
    main()

