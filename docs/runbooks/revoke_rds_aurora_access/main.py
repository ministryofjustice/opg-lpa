import argparse
import logging
import botocore
import boto3
import json

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()


class RemoveRDSSecurityGroupRules:
    aws_ec2_client = ""
    account_id = ""

    def __init__(self, region):
        self.aws_ec2_client = boto3.client("ec2", region_name=region)
        self.account_id = boto3.client("sts").get_caller_identity().get("Account")
        region = self.aws_ec2_client.meta.region_name
        print(region)

    def get_security_group_id(self, security_group_name_prefix):
        return self.aws_ec2_client.describe_security_groups(
            Filters=[
                {
                    "Name": "group-name",
                    "Values": [
                        security_group_name_prefix,
                    ],
                },
            ]
        )["SecurityGroups"][0]["GroupId"]

    def get_security_group_egress_rule(self, security_group_id):
        return self.aws_ec2_client.describe_security_group_rules(
            Filters=[
                {
                    "Name": "group-id",
                    "Values": [
                        security_group_id,
                    ],
                },
            ]
        )["SecurityGroupRules"]

    def revoke_security_group_egress(self, security_group_id, security_group_rule_id):
        return self.aws_ec2_client.revoke_security_group_egress(
            GroupId=security_group_id,
            SecurityGroupRuleIds=[security_group_rule_id],
            DryRun=False,
        )

    def revoke_security_group_ingress(self, security_group_id, security_group_rule_id):
        return self.aws_ec2_client.revoke_security_group_ingress(
            GroupId=security_group_id,
            SecurityGroupRuleIds=[security_group_rule_id],
            DryRun=False,
        )

    def remove_sg_rules(self, environment_name):
        logger.info("environment: {}".format(environment_name))
        security_group_name_prefixes = [
            f"rds-proxy-{environment_name}*",
            f"rds-api-{environment_name}*",
        ]
        for security_group_name_prefix in security_group_name_prefixes:
            try:
                security_group_id = self.get_security_group_id(
                    security_group_name_prefix
                )
                logger.info(
                    "found security group {} id: {}".format(
                        security_group_name_prefix, security_group_id
                    )
                )

                security_group_egress_rules = self.get_security_group_egress_rule(
                    security_group_id
                )

                if len(security_group_egress_rules) > 0:
                    for rule in security_group_egress_rules:
                        rule_id = rule["SecurityGroupRuleId"]
                        if rule["IsEgress"] == True:
                            logger.info(
                                "removing SG Egress rule {} for: {}".format(
                                    rule_id, security_group_name_prefix
                                )
                            )
                            response = self.revoke_security_group_egress(
                                security_group_id, rule_id
                            )
                            if response["Return"]:
                                logger.info("removed.")
                        else:
                            logger.info(
                                "removing SG Ingress rule {} for: {}".format(
                                    rule_id, security_group_name_prefix
                                )
                            )
                            response = self.revoke_security_group_ingress(
                                security_group_id, rule_id
                            )
                            if response["Return"]:
                                logger.info("removed.")
                else:
                    logger.info(
                        "no rule to remove for {}".format(security_group_name_prefix)
                    )

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
    parser = argparse.ArgumentParser(description="Revoke access to Aurora cluster")
    parser.add_argument(
        "--environment_names",
        nargs="+",
        required=True,
        help="Names of the environment to remove Aurora access for",
    )
    parser.add_argument(
        "--region",
        default="eu-west-1",
        help="AWS region to target, defaults to eu-west-1",
    )

    args = parser.parse_args()

    start = RemoveRDSSecurityGroupRules(args.region)

    for environment_name in args.environment_names:
        start.remove_sg_rules(environment_name)


if __name__ == "__main__":
    main()
