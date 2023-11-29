#!/usr/bin/env python

# A script to allow a Cloud9 environment to connect to a Redis instance

import boto3
import botocore
import os
import subprocess


# Clean up the ingress rules that were added to the Redis Security Groups
def cleanup_security_groups():
    redis_clusters = get_redis_clusters()
    for cluster in redis_clusters:
        ec2 = boto3.client("ec2")
        try:
            ec2.revoke_security_group_ingress(
                GroupId=redis_clusters[cluster]["security_group_id"],
                IpPermissions=[
                    {
                        "FromPort": 6379,
                        "ToPort": 6379,
                        "IpProtocol": "tcp",
                        "IpRanges": [
                            {
                                "CidrIp": f"{get_cloud9_ip_address()}/32",
                            }
                        ],
                    }
                ],
            )
            print(
                "Removed ingress rule from Security Group {}".format(
                    redis_clusters[cluster]["security_group_id"]
                )
            )
        except botocore.exceptions.ClientError as e:
            if e.response["Error"]["Code"] == "InvalidPermission.NotFound":
                pass
            else:
                raise e


# Get a list of Redis clusters in the AWS account
def get_redis_clusters():
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


def get_redis_rg_endpoint(cluster_id):
    return boto3.client("elasticache").describe_replication_groups(
        ReplicationGroupId=cluster_id
    )["ReplicationGroups"][0]["NodeGroups"][0]["PrimaryEndpoint"]["Address"]


# Get the Security Group ID for the Cloud9 instance
def get_cloud9_ip_address():
    return (
        os.popen("curl -s http://169.254.169.254/latest/meta-data/local-ipv4")
        .read()
        .strip()
    )


# Update the passed in Security Group to allow ingress from the Cloud9 Security Group
def update_ingress_security_group(security_group_id):
    source_ip_address = get_cloud9_ip_address()
    ec2 = boto3.client("ec2")
    try:
        ec2.authorize_security_group_ingress(
            GroupId=security_group_id,
            IpPermissions=[
                {
                    "FromPort": 6379,
                    "ToPort": 6379,
                    "IpProtocol": "tcp",
                    "IpRanges": [
                        {
                            "CidrIp": f"{source_ip_address}/32",
                        }
                    ],
                }
            ],
        )
        print(
            "Updated Security Group {} to allow ingress from {}".format(
                redis_clusters[cluster]["security_group_id"], source_ip_address
            )
        )
    except botocore.exceptions.ClientError as e:
        if e.response["Error"]["Code"] == "InvalidPermission.Duplicate":
            pass
        else:
            raise e


# Install the Redis CLI
def install_redis_cli():
    if (
        subprocess.call(
            "which redis-cli",
            shell=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
        )
        == 0
    ):
        print("Redis CLI already installed")
        return
    print("Installing Redis CLI")
    os.system("sudo yum install -y gcc make")
    os.system("wget http://download.redis.io/redis-stable.tar.gz")
    os.system("tar xvzf redis-stable.tar.gz")
    os.system("cd redis-stable && make && sudo make install")
    os.system("sudo cp redis-stable/src/redis-cli /usr/local/bin/")
    os.system("sudo chmod 755 /usr/local/bin/redis-cli")


if __name__ == "__main__":
    if len(os.sys.argv) > 1 and os.sys.argv[1] == "cleanup":
        cleanup_security_groups()
        exit(0)

    redis_clusters = get_redis_clusters()
    endpoints = []
    for cluster in redis_clusters:
        update_ingress_security_group(redis_clusters[cluster]["security_group_id"])
        endpoints.append(redis_clusters[cluster]["endpoint"])

    install_redis_cli()
    print("Redis endpoints: {}".format(set(endpoints)))
    print("redis-cli --tls -p 6379 -h <endpoint>")
    print("""""")
    print(
        "Please clean up the Security Group ingress rules when you are done by running the following command:"
    )
    print("./redis_init.py cleanup")
