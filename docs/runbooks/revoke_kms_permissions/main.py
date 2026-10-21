import argparse
import logging
import botocore
import boto3
import json

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()


class PutKMSKeyPolicy:
    aws_kms_client = ""
    account_id = ""

    def __init__(self, region):
        self.aws_kms_client = boto3.client("kms", region_name=region)
        self.account_id = boto3.client("sts").get_caller_identity().get("Account")

    def modify_policy(self, policy_json_path):
        with open(policy_json_path, "r", encoding="utf-8") as json_file:
            policy = json.load(json_file)

        role_arn = f"arn:aws:iam::{self.account_id}:role/breakglass"
        for statement in policy["Statement"]:
            if statement.get("Principal") == {"AWS": "breakglass_role"}:
                statement["Principal"]["AWS"] = role_arn
            condition = statement.get("Condition", {}).get("ArnNotEquals", {})
            if condition.get("aws:PrincipalArn") == "breakglass_role":
                condition["aws:PrincipalArn"] = role_arn
        return policy

    def put_policy(self, key_arn, policy_json_path):
        policy = self.modify_policy(policy_json_path)

        try:
            logger.info("Putting the following policy")
            logger.info(json.dumps(policy, indent=4))
            logger.info("Putting policy revoking access for: {}".format(key_arn))

            response = self.aws_kms_client.put_key_policy(
                KeyId=key_arn,
                PolicyName="default",
                Policy=json.dumps(policy),
                BypassPolicyLockoutSafetyCheck=False,
            )
            logger.info(json.dumps(response, indent=4))

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def log_error(self, err):
        logger.error("Error Message: {}".format(err.response["Error"]["Message"]))
        logger.error(
            "Request ID: {}".format(err.response["ResponseMetadata"]["RequestId"])
        )
        logger.error(
            "Http code: {}".format(err.response["ResponseMetadata"]["HTTPStatusCode"])
        )


def main():
    parser = argparse.ArgumentParser(
        description="Revoke access to KMS keys using a key policy."
    )
    parser.add_argument(
        "--key_arns", nargs="+", required=True, help="Specific KMS key ARNs to update"
    )
    parser.add_argument(
        "--revoke", action="store_true", required=True, help="Apply the Deny statement"
    )
    parser.add_argument(
        "--region",
        default="eu-west-1",
        help="AWS region to target, defaults to eu-west-1",
    )

    args = parser.parse_args()

    start = PutKMSKeyPolicy(args.region)
    policy = "./deny_policy.json"

    for key_arn in args.key_arns:
        start.put_policy(key_arn, policy)


if __name__ == "__main__":
    main()
