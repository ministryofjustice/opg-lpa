import boto3
import json
import os
import quopri
import time
import re
import argparse

mailbox_bucket = 'opg-lpa-casper-mailbox' # this might get renamed once casper tests are turned off
activation_emails_path = 'cypress/activation_emails'
parser = argparse.ArgumentParser(description='Monitor S3 bucket for emails sent during tests of Make an LPA.')
parser.add_argument('-v', action="store_true", help='verbose', default=False)
args = parser.parse_args()

def assume_role_and_get_client():
    sts = boto3.client(
        'sts',
        region_name='eu-west-1',
    )

    if os.getenv('CYPRESS_CI'):
        print("S3Monitor starting, Assuming CI role")
        role_arn = 'arn:aws:iam::050256574573:role/opg-lpa-ci'
    else:
        print("S3Monitor starting, Assuming operator role")
        role_arn = 'arn:aws:iam::050256574573:role/operator'

    result = sts.assume_role(
        RoleArn=role_arn,
        RoleSessionName='session1',
    )

    s3Client = boto3.client(
        's3',
        aws_access_key_id=result['Credentials']['AccessKeyId'],
        aws_secret_access_key=result['Credentials']['SecretAccessKey'],
        aws_session_token=result['Credentials']['SessionToken']
    )

    return s3Client

# Extract the plus part from emails of the form:
# basename+pluspart@example.com
def getPlusPartFromEmailAddress(email):
    match = re.search("[^\+]\+(.+)@", email)
    if match is None:
        return ""
    return match.group(1)

def parseBody(bodyContent, subject, thetype, linkRegex):
    regex = 'https:\/\/\S+' + linkRegex + '\/[a-zA-Z0-9]+'

    match = re.search(regex, bodyContent)

    if match is not None:
        s = match.start()
        e = match.end()
        activationLink = bodyContent[s:e]
        printIfVerbose(f'{ thetype } link { activationLink }')

        emailRegex = 'To: (.+\\+.+)\\n'

        emailMatch = re.search(emailRegex, bodyContent)
        if emailMatch is not None:
            toEmail = emailMatch.group(1)

            userId = getPlusPartFromEmailAddress(toEmail)
            printIfVerbose(f'userId {userId}')

            if userId != '':
                contents = f'{toEmail[:-1]},{activationLink}'
                filePath = f'{activation_emails_path}/{userId}.{thetype}'
                emailFile = open(filePath,'w')
                emailFile.write(contents)
                emailFile.close()
                printIfVerbose(f'wrote file for {thetype} email to {filePath}')
            else:
                printIfVerbose(f'could not get valid user ID from email address {toEmail}')
        else:
            printIfVerbose('unable to find email regex to derive the To: field')
    else:
        printIfVerbose(f'Message: {subject} does not match regex {regex}')

def parse_email(bodyContent, s3Key):
    printIfVerbose('\n-------- START PARSE EMAIL')

    activate_subject = 'Subject: Activate your lasting power of attorney account'
    reset_password_subject = 'Subject: Password reset request'
    reset_password_no_account_subject = 'Subject: Request to reset password'

    if re.search(activate_subject, bodyContent, re.IGNORECASE) is not None:
        return parseBody(bodyContent, activate_subject, 'activation', 'signup\/confirm')
    else:
        printIfVerbose('email is not an activation email')

    if re.search(reset_password_subject, bodyContent, re.IGNORECASE) is not None:
        return parseBody(bodyContent, reset_password_subject, 'passwordreset', 'forgot-password\/reset')
    else:
        printIfVerbose('email is not a forgotten password email')

    # handle password resets where the account doesn't exist yet. We may need to test this too ultimately
    if re.search(reset_password_no_account_subject, bodyContent, re.IGNORECASE) is not None:
        printIfVerbose("Found Password reset for a non-existent account. This shouldn't happen during tests, one explanation can be running password reset test before test that signs user up")
        return write_unrecognized_file(s3Key, bodyContent, 'noaccountpasswordreset')
    else:
        printIfVerbose('email is not to reset password for non-active account')

    # handle other emails. Ultimately, we should be testing these other emails as well
    printIfVerbose("Found an email that is not an Activate or Password reset. Don't know what to do with it")
    write_unrecognized_file(s3Key, bodyContent, 'unrecognized')

    printIfVerbose('-------- END PARSE EMAIL\n')

def write_unrecognized_file(s3Key, bodyContent, filePrefix):
    fileSuffix = s3Key[s3Key.rfind('/')+1:]
    filePath = f'{activation_emails_path}/{filePrefix}.{fileSuffix}'
    emailFile = open(filePath,'w')
    emailFile.write(bodyContent)
    emailFile.close()

def process_bucket_object(s3Client, s3Key):
    result = s3Client.get_object(Bucket=mailbox_bucket, Key=s3Key)
    bodyContent = quopri.decodestring(result["Body"].read()).decode('latin-1')
    parse_email(bodyContent, s3Key)

def monitor_bucket(s3Client):
    seenkeys = []

    while True:
        bucketContents = s3Client.list_objects(Bucket=mailbox_bucket)
        if 'Contents' in bucketContents:  # handle bucket being empty
            for s3obj in s3Client.list_objects(Bucket=mailbox_bucket)['Contents']:
                s3Key = s3obj['Key']
                if not s3Key in seenkeys:
                    process_bucket_object(s3Client, s3Key)
                    seenkeys.append(s3Key)
        time.sleep(5)

def printIfVerbose(logOutput):
    if args.v :  # if we specified verbose with -v option
        print(logOutput)


def main():
    s3Client = assume_role_and_get_client()
    monitor_bucket(s3Client)

if __name__ == "__main__":
    main()
