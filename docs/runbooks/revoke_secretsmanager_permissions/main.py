import argparse
import logging
import botocore
import boto3
import json

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()


class PutSecretsManagerResourcePolicy:
    aws_secretsmanager_client = ""
    account_id = ""

    def __init__(self, region):
        self.aws_secretsmanager_client = boto3.client(
            "secretsmanager", region_name=region
        )
        self.account_id = boto3.client("sts").get_caller_identity().get("Account")

    def modify_policy(self, policy_json_path, secret_arn):
        with open(policy_json_path, "r", encoding="utf-8") as json_file:
            policy = json.load(json_file)

        role_arn = f"arn:aws:iam::{self.account_id}:role/breakglass"
        for statement in policy["Statement"]:
            resources = statement["Resource"]
            statement["Resource"] = [
                resource.replace("secret_arn", secret_arn) for resource in resources
            ]
            condition = statement.get("Condition", {}).get("ArnNotEquals", {})
            if condition.get("aws:PrincipalArn") == "breakglass_role":
                condition["aws:PrincipalArn"] = role_arn

        logger.info("Putting the following policy")
        logger.info(json.dumps(policy, indent=4))
        return policy

    def put_policy(self, secret_arn, policy_json_path):
        policy = self.modify_policy(policy_json_path, secret_arn)

        try:
            logger.info("Putting policy revoking access for: {}".format(secret_arn))

            response = self.aws_secretsmanager_client.put_resource_policy(
                SecretId=secret_arn, ResourcePolicy=json.dumps(policy)
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
        description="Revoke access to Secrets Manager secrets using a resource policy."
    )
    parser.add_argument(
        "--secret_arns",
        nargs="+",
        required=True,
        help="Full ARNs of the secrets to update",
    )
    parser.add_argument(
        "--revoke", action="store_true", required=True, help="Apply the Deny policy"
    )
    parser.add_argument(
        "--region",
        default="eu-west-1",
        help="AWS region to target, defaults to eu-west-1",
    )

    args = parser.parse_args()

    start = PutSecretsManagerResourcePolicy(args.region)
    policy = "./deny_policy.json"

    for secret_arn in args.secret_arns:
        start.put_policy(secret_arn, policy)


if __name__ == "__main__":
    main()
