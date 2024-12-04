#!/usr/bin/env python3

# A script to allow a Cloud9 environment to connect to a Redis or Postgres cluster from Cloud9
# by adding the Cloud9 instance's IP address to the security group of the cluster. This script
# also installs the Redis and Postgres CLI tools if they are not already installed.

import boto3
import botocore
import os
import subprocess
import argparse
import sys
import logging

logging.basicConfig(level=os.environ.get("LOGLEVEL", "INFO"))
logger = logging.getLogger("cloud9_setup")
botocore_logger = logging.getLogger("botocore")
botocore_logger.setLevel(logging.CRITICAL)


def cleanup_security_groups(security_group_ids, port) -> None:
    """Remove the ingress rule from the security group
    Args:
        security_group_ids (list): A list of security group IDs
        port (int): The port number to remove the ingress rule from
    Returns:
        None
    """
    for sg_id in security_group_ids:
        ec2 = boto3.client("ec2")
        try:
            ec2.revoke_security_group_ingress(
                GroupId=sg_id,
                IpPermissions=[
                    {
                        "FromPort": port,
                        "ToPort": port,
                        "IpProtocol": "tcp",
                        "IpRanges": [
                            {
                                "CidrIp": f"{get_cloud9_ip_address()}/32",
                            }
                        ],
                    }
                ],
            )
            logger.info("Removed ingress rule from Security Group {}".format(sg_id))
        except botocore.exceptions.ClientError as e:
            if e.response["Error"]["Code"] == "InvalidPermission.NotFound":
                pass
            else:
                raise e


def cleanup_redis(**kwargs) -> None:
    """Clean up the security groups for the Redis clusters"""
    clusters = get_redis_clusters()
    for cluster in clusters:
        cleanup_security_groups([clusters[cluster]["security_group_id"]], 6379)


def cleanup_postgres(**kwargs) -> None:
    """
    Clean up the security groups for the Postgres clusters.

    This function retrieves the Postgres clusters and cleans up their associated security groups.
    It also deletes the environment variables for the Postgres CLI and clears the bash history.

    Parameters:
    - kwargs: Additional keyword arguments (not used in this function)

    Returns:
    - None
    """
    clusters = get_postgres_clusters()
    for cluster in clusters:
        cleanup_security_groups([clusters[cluster]["security_group_id"]], 5432)
    # Delete the environment variables for the Postgres CLI and clear the bash history
    os.system("unset PGUSER")
    os.system("unset PGPASSWORD")
    os.system("cat /dev/null > ~/.bash_history && history -c && exit")


def setup_all(**kwargs) -> None:
    """
    Call the setup functions for Redis and Postgres.

    Parameters:
    - kwargs (dict): Additional keyword arguments.

    Returns:
    - None
    """
    setup_redis()
    setup_postgres(environment_name=kwargs.get("environment_name"))


def cleanup_all(**kwargs) -> None:
    """
    Call the cleanup functions for Redis and Postgres

    Args:
        kwargs (dict): A dictionary of keyword arguments (not used in this function)

    Returns:
        None
    """
    cleanup_redis()
    cleanup_postgres()


def get_redis_clusters() -> dict:
    """
    Get a list of Redis clusters in the AWS account

    Returns:
        dict: A dictionary of Redis clusters with the cluster ID as the key and the endpoint and security group ID as the values
    """
    clusters = boto3.client("elasticache").describe_cache_clusters()["CacheClusters"]
    redis_clusters = {}
    for cluster in clusters:
        if cluster["Engine"] == "redis":
            replication_group_id = cluster["CacheClusterId"].rsplit("-", 1)[0]
            get_redis_rg_endpoint(replication_group_id)

            redis_clusters[cluster["CacheClusterId"]] = {
                "security_group_id": cluster["SecurityGroups"][0]["SecurityGroupId"],
                "endpoint": get_redis_rg_endpoint(replication_group_id),
                "replication_group_id": replication_group_id,
            }

    return redis_clusters


def setup_redis(**kwargs) -> None:
    """
    Set up the security groups for the Redis and install the Redis CLI (if not already installed)

    Parameters:
    - kwargs: Additional keyword arguments (not used in this function)

    Returns:
    - None
    """
    clusters = get_redis_clusters()
    endpoints = []
    for cluster in clusters:
        add_ingress_rule_security_group(clusters[cluster]["security_group_id"], 6379)
        endpoints.append(clusters[cluster]["endpoint"])

    install_redis_cli()
    print("Redis endpoints: {}".format(set(endpoints)))
    print("""""")
    print("redis-cli --tls -p 6379 -h <endpoint>")
    print("""""")


def setup_postgres(**kwargs) -> None:
    """
    Set up the security groups for the Postgres and install the Postgres CLI (if not already installed)

    Args:
        **kwargs: Additional keyword arguments for setup_postgres function. environment_name is the only supported keyword argument.
    Returns:
        None
    """
    clusters = get_postgres_clusters()
    ro_endpoints, rw_endpoints = [], []
    for cluster in clusters:
        add_ingress_rule_security_group(clusters[cluster]["security_group_id"], 5432)
        rw_endpoints.append(clusters[cluster]["endpoint"])
        ro_endpoints.append(clusters[cluster]["reader_endpoint"])

    # The name of the AWS Secret Manager secret that stores the password for the Postgres database
    postgres_password_aws_secret_name = (
        f"{kwargs.get('environment_name')}/api_rds_password"
    )

    postgres_password = boto3.client("secretsmanager").get_secret_value(
        SecretId=postgres_password_aws_secret_name
    )["SecretString"]

    install_postgres_cli()

    print(
        "Run the following commands to set up the environment variables for the Postgres CLI:"
    )
    print(f"export PGUSER={clusters[cluster]['username']}")
    print(f"export PGPASSWORD={postgres_password}")
    print("""""")
    print("Postgres read-write endpoints: {}".format(set(rw_endpoints)))
    print("Postgres read-only endpoints: {}".format(set(ro_endpoints)))
    print("""""")
    print("psql -h <endpoint>")
    print("""""")


def install_postgres_cli() -> None:
    """
    Install the Postgres CLI if it is not already installed.

    This function checks if the Postgres CLI is already installed by running the `which psql` command.
    If the command returns a zero exit code, it means the CLI is already installed and the function returns.
    Otherwise, the function proceeds to install the Postgres CLI using the `sudo yum install -y postgresql15` command.

    Note: This function assumes that the system is using the yum package manager.

    """
    if (
        subprocess.call(
            "which psql",
            shell=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
        )
        == 0
    ):
        logger.debug("Postgres CLI already installed")
        return
    logger.debug("Installing Postgres CLI")
    os.system("sudo yum install -y postgresql15")


def get_postgres_clusters() -> dict:
    """
    Get a list of Postgres clusters in the AWS account

    Returns:
        dict: A dictionary of Postgres clusters with the cluster ID as the key and the endpoint, reader endpoint, and security group ID as the values
    """
    clusters = boto3.client("rds").describe_db_clusters()["DBClusters"]
    postgres_clusters = {}
    for cluster in clusters:
        if cluster["Engine"] == "aurora-postgresql":
            postgres_clusters[cluster["DBClusterIdentifier"]] = {
                "security_group_id": cluster["VpcSecurityGroups"][0][
                    "VpcSecurityGroupId"
                ],
                "endpoint": cluster["Endpoint"],
                "reader_endpoint": cluster["ReaderEndpoint"],
                "username": cluster["MasterUsername"],
            }
    return postgres_clusters


def get_redis_rg_endpoint(cluster_id) -> str:
    """
    Get the endpoint of the Redis cluster
    Args:
        cluster_id (str): The ID of the Redis cluster
    Returns:
        str: The primary endpoint of the Redis cluster
    """
    return boto3.client("elasticache").describe_replication_groups(
        ReplicationGroupId=cluster_id
    )["ReplicationGroups"][0]["NodeGroups"][0]["PrimaryEndpoint"]["Address"]


# Get the Security Group ID for the Cloud9 instance
def get_cloud9_ip_address() -> str:
    """Get the IP address of the Cloud9 instance
    Returns:
        str: The IP address of the Cloud9 instance
    """
    token = (
        os.popen(
            "curl --silent --request PUT 'http://169.254.169.254/latest/api/token' --header 'X-aws-ec2-metadata-token-ttl-seconds: 3600'"
        )
        .read()
        .strip()
    )
    return (
        os.popen(
            f"curl --silent http://169.254.169.254/latest/meta-data/local-ipv4 --header 'X-aws-ec2-metadata-token: {token}'"
        )
        .read()
        .strip()
    )


def add_ingress_rule_security_group(security_group_id, port) -> None:
    """
    Add an ingress rule to the security group to allow traffic from the Cloud9 instance

    Args:
        security_group_id (str): The ID of the security group
        port (int): The port number to allow traffic on

    Returns:
        None
    """
    source_ip_address = get_cloud9_ip_address()
    ec2 = boto3.client("ec2")
    try:
        ec2.authorize_security_group_ingress(
            GroupId=security_group_id,
            IpPermissions=[
                {
                    "FromPort": port,
                    "ToPort": port,
                    "IpProtocol": "tcp",
                    "IpRanges": [
                        {
                            "CidrIp": f"{source_ip_address}/32",
                        }
                    ],
                }
            ],
        )
        logger.debug(
            "Updated Security Group {} to allow ingress from {}".format(
                security_group_id, source_ip_address
            )
        )
    except botocore.exceptions.ClientError as e:
        if e.response["Error"]["Code"] == "InvalidPermission.Duplicate":
            pass
        else:
            raise e


def install_redis_cli() -> None:
    """
    Install the Redis CLI if it is not already installed

    This function checks if the Redis CLI is already installed by running the command "which redis-cli".
    If the command returns a non-zero exit code, indicating that the Redis CLI is not installed,
    the function proceeds to install it by executing a series of shell commands using the os.system() function.

    The installation process includes installing the necessary dependencies (gcc and make),
    downloading the Redis source code, compiling it with TLS support, and copying the resulting redis-cli binary
    to the /usr/local/bin/ directory.

    Note: This function requires sudo privileges to install the Redis CLI.
    """
    if (
        subprocess.call(
            "which redis-cli",
            shell=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
        )
        == 0
    ):
        logger.debug("Redis CLI already installed")
        return
    logger.debug("Installing Redis CLI")
    os.system("sudo yum install -y gcc make")
    os.system("wget http://download.redis.io/redis-stable.tar.gz")
    os.system("tar xvzf redis-stable.tar.gz")
    os.system("cd redis-stable && make BUILD_TLS=yes && sudo make install")
    os.system("sudo cp redis-stable/src/redis-cli /usr/local/bin/")
    os.system("sudo chmod 755 /usr/local/bin/redis-cli")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Connect to Redis or Postgres")

    parser.add_argument(
        "--service",
        type=str,
        default="all",
        choices=["redis", "postgres", "all"],
        help="The service to connect to (redis, postgres or all). Default is all",
    )
    parser.add_argument(
        "--cleanup", action="store_true", help="Cleanup the security groups only."
    )
    parser.add_argument(
        "--environment",
        type=str,
        default="development",
        help="The name of the environment. Default is development.",
    )

    args = parser.parse_args()

    clusters = {}

    if args.cleanup:
        action = "cleanup"
    else:
        action = "setup"

    logger.info(f"Running {action} on {args.service} service(s)...")

    # Run the appropriate function based on the service and action specified in the arguments. e.g. setup_redis(), cleanup_postgres(), etc.
    try:
        getattr(sys.modules[__name__], f"{action}_{args.service}")(
            environment_name=args.environment
        )
    except AttributeError:
        logger.fatal(f"Service {args.service} is not supported")

    if args.cleanup:
        logger.debug("Cleanup has been run.")
    else:
        print(
            "Please clean up the Security Group ingress rules when you are done by running the following command:"
        )
        print(f"{sys.argv[0]} --service {args.service} --cleanup")
