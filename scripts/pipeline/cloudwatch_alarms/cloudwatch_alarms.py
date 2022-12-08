# A script to disable unnecessary CloudWatch alarm actions during a deployment to prevent false positives

import boto3
from botocore.exceptions import ClientError
import logging
import sys

logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)

if len(sys.argv) != 3:
    raise Exception("Usage: python cloudwatch_alarms.py <alarm name> <action>")

alarm_names = sys.argv[1].split(',')
alarm_action = sys.argv[2].lower()

if alarm_action not in ['enable', 'disable']:
    raise Exception("Action must be 'enable' or 'disable'")


class CloudWatchWrapper:
    """Encapsulates Amazon CloudWatch functions."""
    def __init__(self, cloudwatch_resource):
        """
        :param cloudwatch_resource: A Boto3 CloudWatch resource.
        """
        self.cloudwatch_resource = cloudwatch_resource

    def enable_alarm_actions(self, alarm_name, enable):
        """
        Enables or disables actions on the specified alarm. Alarm actions can be
        used to send notifications or automate responses when an alarm enters a
        particular state.

        :param alarm_name: The name of the alarm.
        :param enable: When True, actions are enabled for the alarm. Otherwise, they
                       disabled.
        """
        try:
            alarm = self.cloudwatch_resource.Alarm(alarm_name)
            if enable:
                alarm.enable_actions()
            else:
                alarm.disable_actions()
            logger.info(
                "%s actions for alarm %s.", "Enabled" if enable else "Disabled",
                alarm_name)
        except ClientError:
            logger.exception(
                "Couldn't %s actions alarm %s.", "enable" if enable else "disable",
                alarm_name)
            raise


if __name__ == "__main__":
    # Set up logging

    # Set up AWS session
    session = boto3.Session()
    cloudwatch = session.resource("cloudwatch")

    # Enable actions on the alarm
    cw = CloudWatchWrapper(cloudwatch)

    for alarm_name in alarm_names:
        if alarm_action == "enable":
            logger.info("Enabling actions on alarm %s.", alarm_name)
            cw.enable_alarm_actions(alarm_name, True)
        elif alarm_action == "disable":
            logger.info("Disabling actions on alarm %s.", alarm_name)
            cw.enable_alarm_actions(alarm_name, False)
        else:
            logger.error("Invalid alarm action: %s", alarm_action)
            sys.exit(1)
    


    