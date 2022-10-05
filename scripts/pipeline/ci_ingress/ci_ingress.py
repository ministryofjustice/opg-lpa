import urllib.request
import argparse
import json
import os
import logging
import boto3
import time

logger = logging.getLogger()
logging.basicConfig(encoding="utf-8", level=logging.INFO)


class IngressManager:
    aws_account_id = ""
    aws_iam_session = ""
    aws_ec2_client = ""
    security_groups = []

    def __init__(self, config_file):
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()
        self.aws_ec2_client = boto3.client(
            "ec2",
            region_name=self.aws_region,
            aws_access_key_id=self.aws_iam_session["Credentials"]["AccessKeyId"],
            aws_secret_access_key=self.aws_iam_session["Credentials"][
                "SecretAccessKey"
            ],
            aws_session_token=self.aws_iam_session["Credentials"]["SessionToken"],
        )

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_region = parameters["region"]
            self.aws_account_id = parameters["account_id"]
            self.security_groups = [
                parameters["front_load_balancer_security_group_id"],
                parameters["admin_load_balancer_security_group_id"],
            ]

    def set_iam_role_session(self):
        if os.getenv("CI"):
            role_arn = "arn:aws:iam::{}:role/opg-lpa-ci".format(self.aws_account_id)
        else:
            role_arn = "arn:aws:iam::{}:role/operator".format(self.aws_account_id)

        sts = boto3.client(
            "sts",
            region_name="eu-west-1",
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName="managing_environment_ingress",
            DurationSeconds=900,
        )
        self.aws_iam_session = session

    def get_ip_addresses(self):
        host_public_cidr = (
            urllib.request.urlopen("https://checkip.amazonaws.com")
            .read()
            .decode("utf8")
            .rstrip()
            + "/32"
        )
        return host_public_cidr

    def get_security_group(self, sg_id):
        return self.aws_ec2_client.describe_security_groups(
            GroupIds=[
                sg_id,
            ],
        )

    def clear_all_ci_ingress_rules_from_sg(self):
        for sg_id in self.security_groups:
            for ip_permissions in self.get_security_group(sg_id)["SecurityGroups"][0][
                "IpPermissions"
            ]:
                for rule in ip_permissions["IpRanges"]:
                    if "Description" in rule and rule["Description"].startswith(
                        "ci ingress"
                    ):
                        print("found ci ingress rule in " + sg_id)
                        timestamp = rule["Description"][-10:]
                        # Only remove rules that are at least an hour old to prevent affecting other Cypress runs
                        if int(timestamp) < int(time.time()) - 3600:
                            logger.info("Ignoring ingress rule in %s due to age", sg_id)
                            continue
                        else:
                            try:
                                logger.info(
                                    "Removing security group ingress rule from %s",
                                    sg_id,
                                )
                                self.aws_ec2_client.revoke_security_group_ingress(
                                    GroupId=sg_id,
                                    IpPermissions=[
                                        {
                                            "FromPort": ip_permissions["FromPort"],
                                            "IpProtocol": ip_permissions["IpProtocol"],
                                            "IpRanges": [rule],
                                            "ToPort": ip_permissions["ToPort"],
                                        },
                                    ],
                                )
                                if self.verify_ingress_rule(sg_id):
                                    logger.info(
                                        "Verify: Found security group rule that should have been removed from %s",
                                        sg_id,
                                    )
                                    exit(1)
                            except Exception as exception:
                                logger.info(exception)
                                exit(1)

    def verify_ingress_rule(self, sg_id):
        sg_rules = self.get_security_group(sg_id)["SecurityGroups"][0]["IpPermissions"][
            0
        ]["IpRanges"]

        for sg_rule in sg_rules:
            if "Description" in sg_rule and sg_rule["Description"].startswith(
                "ci ingress"
            ):
                logger.info(sg_rule)
                return True

    def add_ci_ingress_rule_to_sg(self, ingress_cidr):
        self.clear_all_ci_ingress_rules_from_sg()
        try:
            for sg_id in self.security_groups:
                logger.info("Adding SG rule to %s", sg_id)
                self.aws_ec2_client.authorize_security_group_ingress(
                    GroupId=sg_id,
                    IpPermissions=[
                        {
                            "FromPort": 443,
                            "IpProtocol": "tcp",
                            "IpRanges": [
                                {
                                    "CidrIp": ingress_cidr,
                                    "Description": "ci ingress %s" % int(time.time()),
                                },
                            ],
                            "ToPort": 443,
                        },
                    ],
                )
                if self.verify_ingress_rule(sg_id):
                    logger.info("Added ingress rule to %s", sg_id)
        except Exception as exception:
            logger.info(exception)


def main():
    parser = argparse.ArgumentParser(
        description="Add or remove your host's IP address to the app loadbalancer ingress rules."
    )

    parser.add_argument(
        "config_file_path", type=str, help="Path to config file produced by terraform"
    )
    parser.add_argument(
        "--add",
        dest="action_flag",
        action="store_const",
        const=True,
        default=False,
        help="add host IP address to security group ci ingress rule (default: remove all ci ingress rules)",
    )

    args = parser.parse_args()

    work = IngressManager(args.config_file_path)
    ingress_cidr = work.get_ip_addresses()
    if args.action_flag:
        work.add_ci_ingress_rule_to_sg(ingress_cidr)
    else:
        work.clear_all_ci_ingress_rules_from_sg()


if __name__ == "__main__":
    main()
