import boto3
import json
import quopri
import time
import re
import argparse
from pathlib import Path


class S3Monitor:

    MAILBOX_BUCKET = "opg-lpa-casper-mailbox"
    ACTIVATION_EMAILS_PATH = (Path(__file__).parent / "activation_emails").resolve()

    def __init__(self, options):
        """
        :param options: dict with v (verbose) and c (is in CI) boolean properties
        """
        self.verbose = options["v"]
        self.in_ci = options["c"]
        self.s3Client = self.assume_role_and_get_client()

    def assume_role_and_get_client(self):
        sts = boto3.client(
            "sts",
            region_name="eu-west-1",
        )

        if self.in_ci:
            print("S3Monitor starting, Assuming CI role")
            role_arn = "arn:aws:iam::050256574573:role/opg-lpa-ci"
        else:
            print("S3Monitor starting, Assuming operator role")
            role_arn = "arn:aws:iam::050256574573:role/operator"

        result = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName="session1",
        )

        return boto3.client(
            "s3",
            aws_access_key_id=result["Credentials"]["AccessKeyId"],
            aws_secret_access_key=result["Credentials"]["SecretAccessKey"],
            aws_session_token=result["Credentials"]["SessionToken"],
        )

    # Extract the plus part from emails of the form:
    # basename+pluspart@example.com
    def getPlusPartFromEmailAddress(self, email):
        match = re.search("[^\+]\+(.+)@", email)
        if match is None:
            return ""
        return match.group(1)

    def parseBody(self, bodyContent, subject, thetype, linkRegex):
        regex = "https:\/\/\S+" + linkRegex + "\/[a-zA-Z0-9]+"

        match = re.search(regex, bodyContent)

        if match is not None:
            s = match.start()
            e = match.end()
            activationLink = bodyContent[s:e]
            self.printIfVerbose(f"{ thetype } link { activationLink }")

            emailRegex = "To: (.+\\+.+)\\n"

            emailMatch = re.search(emailRegex, bodyContent)
            if emailMatch is not None:
                toEmail = emailMatch.group(1)

                userId = self.getPlusPartFromEmailAddress(toEmail)
                self.printIfVerbose(f"userId {userId}")

                if userId != "":
                    contents = f"{toEmail[:-1]},{activationLink}"
                    filePath = (
                        Path(S3Monitor.ACTIVATION_EMAILS_PATH) / f"{userId}.{thetype}"
                    )
                    emailFile = open(filePath, "w")
                    emailFile.write(contents)
                    emailFile.close()
                    self.printIfVerbose(f"wrote file for {thetype} email to {filePath}")
                else:
                    self.printIfVerbose(
                        f"could not get valid user ID from email address {toEmail}"
                    )
            else:
                self.printIfVerbose(
                    "unable to find email regex to derive the To: field"
                )
        else:
            self.printIfVerbose(f"Message: {subject} does not match regex {regex}")

    def parse_email(self, bodyContent, s3Key):
        self.printIfVerbose("\n-------- START PARSE EMAIL")

        activate_subject = "Subject: Activate your lasting power of attorney account"
        reset_password_subject = "Subject: Password reset request"
        reset_password_no_account_subject = "Subject: Request to reset password"

        if re.search(activate_subject, bodyContent, re.IGNORECASE) is not None:
            return self.parseBody(
                bodyContent, activate_subject, "activation", "signup\/confirm"
            )
        else:
            self.printIfVerbose("email is not an activation email")

        if re.search(reset_password_subject, bodyContent, re.IGNORECASE) is not None:
            return self.parseBody(
                bodyContent,
                reset_password_subject,
                "passwordreset",
                "forgot-password\/reset",
            )
        else:
            self.printIfVerbose("email is not a forgotten password email")

        # handle password resets where the account doesn't exist yet. We may need to test this too ultimately
        if (
            re.search(reset_password_no_account_subject, bodyContent, re.IGNORECASE)
            is not None
        ):
            self.printIfVerbose(
                "Found Password reset for a non-existent account. This shouldn't happen during tests, one explanation can be running password reset test before test that signs user up"
            )
            return self.write_unrecognized_file(
                s3Key, bodyContent, "noaccountpasswordreset"
            )
        else:
            self.printIfVerbose("email is not to reset password for non-active account")

        # handle other emails. Ultimately, we should be testing these other emails as well
        self.printIfVerbose(
            "Found an email that is not an Activate or Password reset. Don't know what to do with it"
        )
        self.write_unrecognized_file(s3Key, bodyContent, "unrecognized")

        self.printIfVerbose("-------- END PARSE EMAIL\n")

    def write_unrecognized_file(self, s3Key, bodyContent, filePrefix):
        fileSuffix = s3Key[s3Key.rfind("/") + 1 :]
        filePath = Path(S3Monitor.ACTIVATION_EMAILS_PATH) / f"{filePrefix}.{fileSuffix}"
        emailFile = open(filePath, "w")
        emailFile.write(bodyContent)
        emailFile.close()

    def process_bucket_object(self, s3Key):
        result = self.s3Client.get_object(Bucket=S3Monitor.MAILBOX_BUCKET, Key=s3Key)
        bodyContent = quopri.decodestring(result["Body"].read()).decode("latin-1")
        self.parse_email(bodyContent, s3Key)

    def printIfVerbose(self, logOutput):
        if self.verbose:
            print(logOutput)

    def run(self):
        seenkeys = []

        while True:
            bucketContents = self.s3Client.list_objects(Bucket=S3Monitor.MAILBOX_BUCKET)
            if "Contents" in bucketContents:  # handle bucket being empty
                for s3obj in self.s3Client.list_objects(
                    Bucket=S3Monitor.MAILBOX_BUCKET
                )["Contents"]:
                    s3Key = s3obj["Key"]
                    if not s3Key in seenkeys:
                        self.process_bucket_object(s3Key)
                        seenkeys.append(s3Key)
            time.sleep(5)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Monitor S3 bucket for emails sent during tests of Make an LPA"
    )
    parser.add_argument("-v", action="store_true", help="verbose", default=False)
    parser.add_argument(
        "-c", action="store_true", help="set flag if running in CI", default=False
    )
    args = parser.parse_args()

    S3Monitor(vars(args)).run()
