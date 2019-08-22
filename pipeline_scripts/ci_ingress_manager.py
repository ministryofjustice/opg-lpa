import urllib.request
import boto3
import argparse
import json
import os
import pprint


class IngressManager:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ec2_client = ''
    workspace = os.getenv('TF_WORKSPACE')
    security_groups = [str(workspace) + "-front-loadbalancer",
                       str(workspace) + "-admin-loadbalancer"]

    def __init__(self, config_file):
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()
        self.aws_ec2_client = boto3.client(
            'ec2',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_account_id = parameters['account_id']

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/ci'.format(self.aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{}:role/account-write'.format(
                self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='modifying_ingress',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_ip_addresses(self):
        host_public_cidr = urllib.request.urlopen(
            'https://checkip.amazonaws.com').read().decode('utf8').rstrip() + "/32"
        return host_public_cidr

    def get_security_group(self, sg_name):
        return self.aws_ec2_client.describe_security_groups(
            GroupNames=[
                sg_name,
            ],
        )

    def clear_all_ci_ingress_rules_from_sg(self):
        pp = pprint.PrettyPrinter(indent=4)
        for sg_name in self.security_groups:

            for from_port in self.get_security_group(sg_name)[
                    'SecurityGroups'][0]['IpPermissions']:
                if 'FromPort' in from_port and from_port['FromPort'] == 443:
                    sg_rules = from_port['IpRanges']
                    for sg_rule in sg_rules:
                        if 'Description' in sg_rule and sg_rule['Description'] == "ci ingress":
                            cidr_range_to_remove = sg_rule['CidrIp']
                            print("found ci ingress rule in " + sg_name)
                            try:
                                print("Removing security group ingress rule " + str(sg_rule) + " from " +
                                      sg_name)
                                self.aws_ec2_client.revoke_security_group_ingress(
                                    GroupName=sg_name,
                                    IpPermissions=[
                                        {
                                            'FromPort': 443,
                                            'IpProtocol': 'tcp',
                                            'IpRanges': [
                                                {
                                                    'CidrIp': cidr_range_to_remove,
                                                    'Description': 'ci ingress'
                                                },
                                            ],
                                            'ToPort': 443,
                                        },
                                    ],
                                )
                                if self.verify_ingress_rule(sg_name):
                                    print(
                                        "Verify: Found security group rule that should have been removed from " + str(sg_name))
                                    exit(1)
                            except Exception as e:
                                print(e)
                                exit(1)

    def verify_ingress_rule(self, sg_name):
        for from_port in self.get_security_group(sg_name)[
                'SecurityGroups'][0]['IpPermissions']:
            if 'FromPort' in from_port and from_port['FromPort'] == 443:
                sg_rules = from_port['IpRanges']
                for sg_rule in sg_rules:
                    if 'Description' in sg_rule and sg_rule['Description'] == "ci ingress":
                        print(sg_rule)
                        return True

    def add_ci_ingress_rule_to_sg(self, ingress_cidr):
        self.clear_all_ci_ingress_rules_from_sg()
        try:
            for sg_name in self.security_groups:
                print("Adding SG rule to " + sg_name)
                self.aws_ec2_client.authorize_security_group_ingress(
                    GroupName=sg_name,
                    IpPermissions=[
                        {
                            'FromPort': 443,
                            'IpProtocol': 'tcp',
                            'IpRanges': [
                                {
                                    'CidrIp': ingress_cidr,
                                    'Description': 'ci ingress'
                                },
                            ],
                            'ToPort': 443,
                        },
                    ],
                )
                if self.verify_ingress_rule(sg_name):
                    print("Added ingress rule to " + str(sg_name))
        except Exception as e:
            print(e)


def main():
    parser = argparse.ArgumentParser(
        description="Add or remove your host's IP address to the viewer and actor loadbalancer ingress rules.")

    parser.add_argument("config_file_path", nargs='?', default="/tmp/environment_pipeline_tasks_config.json", type=str,
                        help="Path to config file produced by terraform")
    parser.add_argument('--add', dest='action_flag', action='store_const',
                        const=True, default=False,
                        help='add host IP address to security group ci ingress rule (default: remove all ci ingress rules)')

    args = parser.parse_args()

    work = IngressManager(args.config_file_path)
    ingress_cidr = work.get_ip_addresses()
    if args.action_flag:
        work.add_ci_ingress_rule_to_sg(ingress_cidr)
    else:
        work.clear_all_ci_ingress_rules_from_sg()


if __name__ == "__main__":
    main()
