import argparse
import logging
import botocore
import boto3
import json

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()


class PutS3BucketPolicy:
    aws_s3_client = ""
    account_id = ""

    def __init__(self):
        self.aws_s3_client = boto3.client("s3")
        self.account_id = boto3.client("sts").get_caller_identity().get("Account")

    def modify_policy(self, policy_json_path, bucket_name):
        with open(policy_json_path, "r", encoding="utf-8") as json_file:
            policy = json.load(json_file)

        bucket_arn = f"arn:aws:s3:::{bucket_name}"
        role_arn = f"arn:aws:iam::{self.account_id}:role/breakglass"
        for statement in policy["Statement"]:
            resources = statement["Resource"]
            statement["Resource"] = [
                resource.replace("bucket_arn", bucket_arn) for resource in resources
            ]
            condition = statement.get("Condition", {}).get("ArnNotEquals", {})
            if condition.get("aws:PrincipalArn") == "breakglass_role":
                condition["aws:PrincipalArn"] = role_arn

        logger.info("Putting the following policy")
        logger.info(json.dumps(policy, indent=4))
        return policy

    def put_policy(self, bucket_name, policy_json_path):
        policy = self.modify_policy(policy_json_path, bucket_name)

        try:
            logger.info("Putting policy revoking access for: {}".format(bucket_name))

            response = self.aws_s3_client.put_bucket_policy(
                Bucket=bucket_name,
                Policy=json.dumps(policy),
                ExpectedBucketOwner=self.account_id,
                ConfirmRemoveSelfBucketAccess=False,
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
        description="Revoke access to S3 buckets using a bucket policy."
    )
    parser.add_argument(
        "--bucket_names",
        nargs="+",
        required=True,
        help="Names of the S3 buckets to update",
    )
    parser.add_argument(
        "--revoke", action="store_true", required=True, help="Apply the Deny policy"
    )

    args = parser.parse_args()

    start = PutS3BucketPolicy()
    policy = "./deny_policy.json"

    for bucket_name in args.bucket_names:
        start.put_policy(bucket_name, policy)


if __name__ == "__main__":
    main()
