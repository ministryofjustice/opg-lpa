import requests
import argparse
import json
import os


class HealthcheckTester:
    front_url = ""
    expected_tag = ""
    healthcheck_status_code = ""
    deployed_tag = ""

    def __init__(self, config_file):
        self.read_parameters_from_file(config_file)
        self.read_healthcheck()

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.front_url = "https://{}/ping/json".format(
                parameters["public_facing_fqdn"]
            )
            self.expected_tag = parameters["tag"]

    def read_healthcheck(self):
        print("Reading health check for {}...".format(self.front_url))
        with requests.get(url=self.front_url) as healthcheck_response:
            self.healthcheck_status_code = healthcheck_response.status_code
            self.deployed_tag = healthcheck_response.json()["tag"]

    def check_status_code(self):
        print("Status code check...")
        if self.healthcheck_status_code == 200:
            print("HTTP status: 200")
        else:
            print(
                "Error with health check. HTTP status: {}. Expected HTTP status 200".format(
                    self.healthcheck_status_code
                )
            )
            exit(1)

    def check_deployed_tag(self):
        print("Deployed tag check...")
        if self.deployed_tag == self.expected_tag:
            print("Deployed tag: {}".format(self.deployed_tag))
        else:
            print(
                "Error with health check. Deployed tag: {0}. Expected {1}.".format(
                    self.deployed_tag, self.expected_tag
                )
            )
            exit(1)


def main():
    parser = argparse.ArgumentParser(
        description="Evaluate the healthcheck endpoint of the online LPA service."
    )

    parser.add_argument(
        "config_file_path",
        nargs="?",
        default="/tmp/environment_pipeline_tasks_config.json",
        type=str,
        help="Path to config file produced by terraform",
    )

    args = parser.parse_args()

    work = HealthcheckTester(args.config_file_path)
    work.check_status_code()
    work.check_deployed_tag()


if __name__ == "__main__":
    main()
