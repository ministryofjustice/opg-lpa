import argparse
import logging
import botocore
import boto3
import json
from datetime import datetime, timezone

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()


class RevokeUserSession:
    aws_iam_client = ""

    def __init__(self):
        self.aws_iam_client = boto3.client("iam", region_name="us-east-1")

    def log_error(self, err):
        logger.error("Error Message: {}".format(err.response["Error"]["Message"]))
        logger.error(
            "Request ID: {}".format(err.response["ResponseMetadata"]["RequestId"])
        )
        logger.error(
            "Http code: {}".format(err.response["ResponseMetadata"]["HTTPStatusCode"])
        )

    def modify_policy(self, policy_json_path, time):
        with open(policy_json_path, "r", encoding="utf-8") as json_file:
            policy = json.load(json_file)
        for statement in policy["Statement"]:
            statement["Condition"]["DateLessThan"]["aws:TokenIssueTime"] = time
        logger.info("Putting the following policy")
        logger.info(json.dumps(policy, indent=4))
        return policy

    def datestamp(self):
        # return current time in format "YYYY-MM-DDTHH:mm:ssZ"
        revoke_time = datetime.now(tz=timezone.utc).strftime("%FT%XZ")
        logger.info("Sessions before {} will be revoked".format(revoke_time))
        return revoke_time

    def revoke_user_session(self, name):
        if name == None:
            logger.error("Missing name. provide with --name")
            raise ValueError()
        time = self.datestamp()
        policy = self.modify_policy("./revoke_session.json", time)
        try:
            logger.info("Revoking session for user: {}".format(name))
            response = self.aws_iam_client.put_user_policy(
                UserName=name,
                PolicyName="AWSRevokeOlderSessions",
                PolicyDocument=json.dumps(policy),
            )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def revoke_role_session(self, name):
        if name == None:
            logger.error("Missing username. provide with --name")
            raise ValueError()
        time = self.datestamp()
        policy = self.modify_policy("./revoke_session.json", time)
        try:
            logger.info("Revoking session for role: {}".format(name))
            response = self.aws_iam_client.put_role_policy(
                RoleName=name,
                PolicyName="AWSRevokeOlderSessions",
                PolicyDocument=json.dumps(policy),
            )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)


def main():
    parser = argparse.ArgumentParser(
        description="Revoke active sessions for a user or role."
    )
    group = parser.add_mutually_exclusive_group()
    group.add_argument(
        "--revoke_user",
        action="store_true",
        help="Revoke active session for an IAM user.",
    )
    group.add_argument(
        "--revoke_role",
        action="store_true",
        help="Revoke active session for an IAM role",
    )
    parser.add_argument(
        "--name",
        help="Arn for the user or role that you wish to revoke active sessions for.",
    )
    args = parser.parse_args()

    start = RevokeUserSession()
    if args.revoke_user:
        start.revoke_user_session(args.name)
    if args.revoke_role:
        start.revoke_role_session(args.name)


if __name__ == "__main__":
    main()
