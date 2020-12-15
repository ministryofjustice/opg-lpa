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

    # Extract the plus part from emails of the form:
    # basename+pluspart@example.com
    def getPlusPartFromEmailAddress(self,email):
        plusPos = email.find('+')
        atPos = email.find('@')
        userIdLength = atPos - plusPos - 1
        userId = email[plusPos + 1:atPos]
        return userId

    def parseBody(self, bodyContent, subject, thetype, linkRegex):
        regex = 'https:\/\/\S+' + linkRegex + '\/[a-zA-Z0-9]+'

        match = re.search(regex, bodyContent)

        if match is not None: 
            s = match.start()
            e = match.end()
            activationLink = bodyContent[s:e]
            print(f'Activation link { activationLink }')

            emailRegex = 'To: (.+)'

            emailMatch = re.search(emailRegex, bodyContent)
            if emailMatch is not None:
                es = emailMatch.start()
                ee = emailMatch.end()
                toEmail = bodyContent[es:ee]

                userId = self.getPlusPartFromEmailAddress(toEmail)
                print(f'userId {userId}')
                contents = f'{toEmail[:-1]},{activationLink}'
                filePath = f'/mnt/test/activation_emails/{userId}.{thetype}'
                print(f'writing {contents} here')
                emailFile = open(filePath,'w')
                emailFile.write(contents)
                emailFile.close()
        else:
            print(f'Message: {subject} does not match regex {regex}') 
            print('----------------------------------------------------------------------------------')
            #print(bodyContent)
            print('----------------------------------------------------------------------------------')

    def monitor_bucket(self):
        seenkeys = []

        while True:
            print('Checking S3')
            bucketContents = self.s3Client.list_objects(Bucket='opg-lpa-casper-mailbox')
            if 'Contents' in bucketContents:  # handle bucket being empty
                for s3obj in self.s3Client.list_objects(Bucket='opg-lpa-casper-mailbox')['Contents']:
                    s3Key = s3obj['Key']
                    if not s3Key in seenkeys:
                        result = self.s3Client.get_object(Bucket='opg-lpa-casper-mailbox',Key=s3Key)
                        bodyContent = quopri.decodestring(result["Body"].read()).decode('latin-1')
                        self.parseBody(bodyContent, 'Activate your lasting power of attorney account', 'activation', 'signup\/confirm')
                        self.parseBody(bodyContent, 'Password reset request', 'passwordreset', 'forgot-password\/reset')
                        seenkeys.append(s3Key)
                    #else:
                    #    print(f'Already seen {s3Key}')
            time.sleep(5)


def main():
    monitor = S3Monitor()
    monitor.set_iam_role_session()
    monitor.monitor_bucket()


if __name__ == "__main__":
    main()

