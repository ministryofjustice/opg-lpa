import argparse
import logging
import botocore
import boto3
from datetime import datetime, timezone
import json

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()


class DeleteUser:
    aws_iam_client = ""

    def __init__(self):
        self.aws_iam_client = boto3.client("iam", region_name="us-east-1")

    def delete(self, username):
        self.revoke_user_session(username)
        self.delete_login_profile(username)
        self.delete_access_keys(username)
        self.delete_signing_certificates(username)
        self.delete_ssh_public_keys(username)
        self.delete_service_specific_credentials(username)
        self.delete_mfa_devices(username)
        self.delete_user_policies(username)
        self.delete_group_memberships(username)
        self.detatch_managed_policies(username)
        self.delete_user(username)

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

    def revoke_user_session(self, username):
        time = self.datestamp()
        policy = self.modify_policy("./revoke_session.json", time)
        try:
            logger.info("Revoking session for user: {}".format(username))
            response = self.aws_iam_client.put_user_policy(
                UserName=username,
                PolicyName="AWSRevokeOlderSessions",
                PolicyDocument=json.dumps(policy),
            )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_login_profile(self, username):
        try:
            logger.info("Deleting login profile: {}".format(username))
            self.aws_iam_client.delete_login_profile(UserName=username)

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_access_keys(self, username):
        try:
            logger.info("Listing access keys for: {}".format(username))
            keys = self.aws_iam_client.list_access_keys(UserName=username)
            for key in keys["AccessKeyMetadata"]:
                logger.info("Deleting access keys {}".format(key["AccessKeyId"]))
                self.aws_iam_client.delete_access_key(
                    UserName=username, AccessKeyId=key["AccessKeyId"]
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_signing_certificates(self, username):
        try:
            logger.info("Listing signing certificates for: {}".format(username))
            certificates = self.aws_iam_client.list_signing_certificates(
                UserName=username
            )
            for certificate in certificates["Certificates"]:
                logger.info(
                    "Deleting signing certificates {}".format(
                        certificate["CertificateId"]
                    )
                )
                self.aws_iam_client.delete_signing_certificate(
                    UserName=username, CertificateId=certificate["CertificateId"]
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_ssh_public_keys(self, username):
        try:
            logger.info("Listing ssh public keys for: {}".format(username))
            ssh_public_keys = self.aws_iam_client.list_ssh_public_keys(
                UserName=username
            )
            for ssh_public_key in ssh_public_keys["SSHPublicKeys"]:
                logger.info(
                    "Deleting ssh public key {}".format(
                        ssh_public_key["SSHPublicKeyId"]
                    )
                )
                self.aws_iam_client.delete_access_key(
                    UserName=username, SSHPublicKeyId=ssh_public_key["SSHPublicKeyId"]
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_service_specific_credentials(self, username):
        try:
            logger.info(
                "Listing all service specific credentials for: {}".format(username)
            )
            service_specific_credentials = (
                self.aws_iam_client.list_service_specific_credentials(UserName=username)
            )
            for service_specific_credential in service_specific_credentials[
                "ServiceSpecificCredentials"
            ]:
                logger.info(
                    "Deleting credential for {}".format(
                        service_specific_credential["ServiceName"]
                    )
                )
                self.aws_iam_client.delete_service_specific_credential(
                    UserName=username,
                    ServiceSpecificCredentialId=service_specific_credential[
                        "ServiceSpecificCredentialId"
                    ],
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_mfa_devices(self, username):
        try:
            logger.info("Listing MFA devices for: {}".format(username))
            mfa_devices = self.aws_iam_client.list_mfa_devices(UserName=username)
            for mfa_device in mfa_devices["MFADevices"]:
                logger.info(
                    "Deactivating MFA with serial {}".format(mfa_device["SerialNumber"])
                )
                self.aws_iam_client.deactivate_mfa_device(
                    UserName=username, SerialNumber=mfa_device["SerialNumber"]
                )
                logger.info(
                    "Deleting MFA with serial {}".format(mfa_device["SerialNumber"])
                )
                self.aws_iam_client.delete_virtual_mfa_device(
                    SerialNumber=mfa_device["SerialNumber"]
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_user_policies(self, username):
        try:
            logger.info("Listing user policies for: {}".format(username))
            user_policies = self.aws_iam_client.list_user_policies(UserName=username)
            for user_policy in user_policies["PolicyNames"]:
                logger.info("Deleting user policy: {}".format(user_policy))
                self.aws_iam_client.delete_user_policy(
                    UserName=username, PolicyName=user_policy
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_group_memberships(self, username):
        try:
            logger.info("Listing groups for: {}".format(username))
            groups = self.aws_iam_client.list_groups_for_user(UserName=username)
            for group in groups["Groups"]:
                logger.info("Removing user from group: {}".format(group["GroupName"]))
                self.aws_iam_client.remove_user_from_group(
                    UserName=username, GroupName=group["GroupName"]
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def detatch_managed_policies(self, username):
        try:
            logger.info("Listing attached managed policies for: {}".format(username))
            attached_policies = self.aws_iam_client.list_attached_user_policies(
                UserName=username
            )
            for attached_policy in attached_policies["AttachedPolicies"]:
                logger.info(
                    "Detatching managed policy: {}".format(
                        attached_policy["PolicyName"]
                    )
                )
                self.aws_iam_client.detach_user_policy(
                    UserName=username, PolicyArn=attached_policy["PolicyArn"]
                )

        except botocore.exceptions.ClientError as err:
            self.log_error(err)

    def delete_user(self, username):
        try:
            logger.info("Deleting user: {}".format(username))
            self.aws_iam_client.delete_user(UserName=username)

        except botocore.exceptions.ClientError as err:
            self.log_error(err)


def main():
    parser = argparse.ArgumentParser(
        description="Print whole month service statistics as JSON."
    )
    parser.add_argument(
        "--username", help="The username to delete, format firstname.lastname"
    )

    args = parser.parse_args()

    start = DeleteUser()
    start.delete(args.username)


if __name__ == "__main__":
    main()
