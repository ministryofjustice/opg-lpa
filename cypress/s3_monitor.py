import argparse
import json
import logging
import quopri
import re
import time

from datetime import datetime, timedelta, timezone
from pathlib import Path

import boto3


class S3Monitor:
    MAILBOX_BUCKET = "opg-lpa-casper-mailbox"
    ACTIVATION_EMAILS_PATH = (Path(__file__).parent / "activation_emails").resolve()

    def __init__(self, options):
        """
        :param options: dict with v (verbose) and c (is in CI) boolean properties
        """
        self.logger = logging.getLogger("s3-monitor")

        logLevel = logging.INFO
        if options["v"]:
            logLevel = logging.DEBUG

        self.logger.setLevel(logLevel)

        ch = logging.StreamHandler()
        ch.setFormatter(logging.Formatter("%(asctime)s - %(levelname)s: %(message)s"))

        self.logger.addHandler(ch)

        self.in_ci = options["c"]
        self.s3Client = self.assume_role_and_get_client()

    def assume_role_and_get_client(self):
        sts = boto3.client(
            "sts",
            region_name="eu-west-1",
        )

        if self.in_ci:
            self.logger.info("S3Monitor starting, Assuming CI role")
            role_arn = "arn:aws:iam::050256574573:role/opg-lpa-ci"
        else:
            self.logger.info("S3Monitor starting, Assuming operator role")
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
            self.logger.debug(f"  - { thetype } link { activationLink }")

            emailRegex = "To: (.+\\+.+)\\n"

            emailMatch = re.search(emailRegex, bodyContent)
            if emailMatch is not None:
                toEmail = emailMatch.group(1)

                userId = self.getPlusPartFromEmailAddress(toEmail)
                self.logger.debug(f"  - userId '{userId}'")

                if userId != "":
                    contents = f"{toEmail[:-1]},{activationLink}"
                    filePath = (
                        Path(S3Monitor.ACTIVATION_EMAILS_PATH) / f"{userId}.{thetype}"
                    )
                    emailFile = open(filePath, "w")
                    emailFile.write(contents)
                    emailFile.close()
                    self.logger.debug(f"  - wrote {thetype} email to {filePath}")
                else:
                    self.logger.error(
                        f"  - ERROR: could not get valid user ID from email address '{toEmail}'"
                    )
            else:
                self.logger.error(
                    "  - ERROR: unable to find email regex to derive the To: field"
                )
        else:
            self.logger.error(f"ERROR: '{subject}' does not match regex '{regex}'")

    def parse_email(self, bodyContent, s3Key):
        activate_subject = "Subject: Activate your lasting power of attorney account"
        reset_password_subject = "Subject: Password reset request"
        reset_password_no_account_subject = "Subject: Request to reset password"

        if re.search(activate_subject, bodyContent, re.IGNORECASE) is not None:
            self.logger.info(f"  FOUND activation email {s3Key}")
            return self.parseBody(
                bodyContent, activate_subject, "activation", "signup\/confirm"
            )

        if re.search(reset_password_subject, bodyContent, re.IGNORECASE) is not None:
            self.logger.info(f"  FOUND password reset email {s3Key}")
            return self.parseBody(
                bodyContent,
                reset_password_subject,
                "passwordreset",
                "forgot-password\/reset",
            )

        # handle password resets where the account doesn't exist yet. We may need to test this too ultimately
        if (
            re.search(reset_password_no_account_subject, bodyContent, re.IGNORECASE)
            is not None
        ):
            self.logger.error(f"  ERROR: Password reset email for non-existent account")
            self.logger.error(
                "    - it's possible that the user has not been activated yet"
            )
            return self.write_unrecognised_file(
                s3Key, bodyContent, "noaccountpasswordreset"
            )

        # handle other emails. Ultimately, we should be testing these other emails as well
        self.logger.error(
            "  ERROR: Found an email that is NOT for activation or password reset; counting as unrecognised"
        )
        self.write_unrecognised_file(s3Key, bodyContent, "unrecognised")

    def write_unrecognised_file(self, s3Key, bodyContent, filePrefix):
        fileSuffix = s3Key[s3Key.rfind("/") + 1 :]
        filePath = Path(S3Monitor.ACTIVATION_EMAILS_PATH) / f"{filePrefix}.{fileSuffix}"
        emailFile = open(filePath, "w")
        emailFile.write(bodyContent)
        emailFile.close()

    def process_bucket_object(self, s3Key):
        result = self.s3Client.get_object(Bucket=S3Monitor.MAILBOX_BUCKET, Key=s3Key)
        bodyContent = quopri.decodestring(result["Body"].read()).decode("latin-1")
        self.parse_email(bodyContent, s3Key)

    def run(self):
        seenkeys = set()

        marker = None

        while True:
            polledAt = datetime.now(timezone.utc)

            self.logger.info(
                f"+++++++ POLLING S3 email bucket {S3Monitor.MAILBOX_BUCKET} at {polledAt}"
            )

            args = {
                "Bucket": S3Monitor.MAILBOX_BUCKET,
                "MaxKeys": 1000,
            }

            if marker is None:
                self.logger.debug("Fetching from start of bucket")
            else:
                self.logger.debug(
                    "USING MARKER: last result set was truncated; starting this fetch from"
                )
                self.logger.debug(f"  {marker}")
                args["Marker"] = marker

            bucketContents = self.s3Client.list_objects(**args)

            if "Contents" in bucketContents:  # handle bucket being empty
                for s3obj in bucketContents["Contents"]:
                    key = s3obj["Key"]

                    if key in seenkeys:
                        #                         self.logger.debug(f"✘ IGNORING {key} - already seen")
                        continue

                    seenkeys.add(key)

                    # emails delivered up to 5 minutes ago might be of interest;
                    # we include these in case there's some delay between the test
                    # start time and the poller start time, e.g. test delivers an
                    # email to the mailbox before the poller is up
                    onlyLastModifiedAfter = polledAt - timedelta(minutes=5)
                    if s3obj["LastModified"] < onlyLastModifiedAfter:
                        self.logger.debug(f"✘ IGNORING {key}")
                        self.logger.debug(
                            f"  - last modified date '{s3obj['LastModified']}' "
                            + "more than 5 minutes ago"
                        )
                        continue

                    self.logger.info(f"✔ PROCESSING {key}")
                    self.process_bucket_object(key)

            # cope with situations where we have more than 1000 emails in the bucket:
            # fetch the next batch, then the next etc. until we get a full
            # result set; at that point, the marker will be reset and we'll start
            # fetching MaxKeys objects in the list from the start again
            marker = None
            if bucketContents["IsTruncated"]:
                marker = key

            time.sleep(2)


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
