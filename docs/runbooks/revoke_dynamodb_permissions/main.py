import argparse
import logging
import botocore
import boto3
import json

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()


class PutDynamoDBResourcePolicy:
    aws_dynamodb_client = ""
    account_id = ""

    def __init__(self, region):
        self.aws_dynamodb_client = boto3.client("dynamodb", region_name=region)
        self.account_id = boto3.client("sts").get_caller_identity().get("Account")

    def modify_policy(self, policy_json_path, table_arn):
        with open(policy_json_path, "r", encoding="utf-8") as json_file:
            policy = json.load(json_file)
        for statement in policy["Statement"]:
            statement["Resource"] = table_arn
            if statement["Principal"]["AWS"] == "breakglass_role":
                statement["Principal"][
                    "AWS"
                ] = f"arn:aws:iam::{self.account_id}:role/breakglass"
        logger.info("Putting the following policy")
        logger.info(json.dumps(policy, indent=4))
        return policy

    def put_policy(self, table_arn, policy_json_path):
        policy = self.modify_policy(policy_json_path, table_arn)

        try:
            logger.info("Putting policy revoking access for: {}".format(table_arn))

            response = self.aws_dynamodb_client.put_resource_policy(
                ResourceArn=table_arn,
                Policy=json.dumps(policy),
                ConfirmRemoveSelfResourceAccess=False,
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
        description="Revoke access to a DynamoDB table using a resource policy."
    )
    parser.add_argument(
        "--table_arns",
        nargs="+",
        required=True,
        help="ARNs of the DynamoDB tables to update",
    )
    parser.add_argument(
        "--revoke", action="store_true", help="ARN of the DynamoDB table to update"
    )
    parser.add_argument(
        "--region",
        default="eu-west-1",
        help="AWS region to target, defaults to eu-west-1",
    )

    args = parser.parse_args()

    start = PutDynamoDBResourcePolicy(args.region)
    if args.revoke:
        policy = "./deny_policy.json"
    else:
        policy = "./allow_policy.json"

    for table_arn in args.table_arns:
        start.put_policy(table_arn, policy)


if __name__ == "__main__":
    main()
