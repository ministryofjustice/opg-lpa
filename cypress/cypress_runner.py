import os
import sys
from argparse import ArgumentParser
from pathlib import Path
from subprocess import Popen
from urllib.parse import urlparse

from stitch import stitch_feature_files
from user_number import generate_user_number


def build_cypress_command(command, env={}):
    """
    :param command: path to script to run cypress
    :param env: dict in format:
        {
            "baseUrl": <front end URL>,
            "adminUrl": <admin URL>,
            "CI": <true if we are in CI>,

            # These set -e Cypress-scoped "environment" vars (see below)
            "userNumber": <user number for sign up>,
            "GLOB": <GLOB for feature files>
            "TAGS": <TAGS to use for filtering features>,
            "stepDefinitions": <location of cypress *.js step definition files>,
            "filterSpecs": <true to filter specs according to TAGS>
        }


    If CYPRESS_CI is true, fixtures are disabled. This is important,
    as if this flag is not set in CI, the tests will fail as the build
    tries to remove fixtures, which it can't do due to networking
    restrictions.

    We mostly configure cypress-cucumber-preprocessor through
    the -e cypress flag, which sets Cypress-scoped variables; see
    https://github.com/badeball/cypress-cucumber-preprocessor/blob/master/docs/configuration.md

    baseUrl and adminUrl are used to set CYPRESS_* variables, as setting
    these with -e doesn't seem to have the desired effect (i.e. CYPRESS_baseUrl=...
    and -e baseUrl=... don't appear to be equivalent)
    """
    command = f"{command} run --headless --config video=false"

    if len(env) > 0:
        # variables which can be passed to cypress directly via the -e flag
        e_vars = ",".join(
            [
                f'{key}="{value}"'
                for key, value in env.items()
                if key not in ["baseUrl", "adminUrl"]
            ]
        )

        if len(e_vars) > 0:
            command = f"{command} -e {e_vars}"

        env_vars = " ".join(
            [
                f'CYPRESS_{key}="{value}"'
                for key, value in env.items()
                if key in ["baseUrl", "adminUrl"]
            ]
        )

        if len(env_vars) > 0:
            command = f"{env_vars} {command}"

    return command


# if value is False, but the environment variable is set to a truthy
# value, it overrides the default; this is because all of our boolean
# argparse arguments default to False
def env_override_bool(value, env_var_name):
    if value is False:
        value = os.environ.get(env_var_name) in ["True", "true", "1"]
    return value


def env_override_string(value, env_var_name, default=None):
    if value is None:
        value = os.environ.get(env_var_name)
    if value is None:
        value = default
    return value


# if using this function to fall back, the value in the environment
# variable denoted by env_var_name should have a format like:
# "@SignUp,@StitchedPF|@SignUp,@StitchedHW"; the string is split on
# the "|" to produce a list of values
def env_override_list(value, env_var_name, default=None):
    if value is None:
        value = os.environ.get(env_var_name)
        if value is None:
            value = default
        else:
            value = value.split("|")
    return value


def get_settings(args_in):
    _parent_dir = Path(__file__).parent

    parser = ArgumentParser(description="Run cypress features")
    parser.add_argument(
        "-n",
        "--no-stitch",
        action="store_true",
        help="set to disable stitching feature files together; or set with $CYPRESS_RUNNER_NO_STITCH=true",
    )
    parser.add_argument(
        "-c",
        "--in-ci",
        action="store_true",
        help="set if running S3 monitor in the CI environment; or set with $CYPRESS_RUNNER_IN_CI=true",
    )
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="set to make S3 monitor provide verbose output; or set with $CYPRESS_RUNNER_VERBOSE=true",
    )
    parser.add_argument(
        "-u",
        "--base-url",
        help='base URL of the front end (path is ignored); or set with $CYPRESS_RUNNER_BASE_URL="url"',
    )
    parser.add_argument(
        "-a",
        "--admin-url",
        help='base URL of the admin app (path is ignored); or set with $CYPRESS_RUNNER_ADMIN_URL="url"',
    )
    parser.add_argument(
        "-t",
        "--tags",
        nargs="*",
        help='tag groups for cypress to run; or set with $CYPRESS_RUNNER_TAGS="@tag1,@tag2|@tag3,@tag4 or @tag5 etc."',
    )
    args = parser.parse_args(args_in)

    should_stitch = not env_override_bool(args.no_stitch, "CYPRESS_RUNNER_NO_STITCH")
    in_ci = env_override_bool(args.in_ci, "CYPRESS_RUNNER_IN_CI")
    verbose = env_override_bool(args.verbose, "CYPRESS_RUNNER_VERBOSE")

    base_url = env_override_string(
        args.base_url, "CYPRESS_RUNNER_BASE_URL", "https://localhost:7002"
    )
    admin_url = env_override_string(
        args.admin_url, "CYPRESS_RUNNER_ADMIN_URL", "https://localhost:7003"
    )
    tags = env_override_list(args.tags, "CYPRESS_RUNNER_TAGS")

    # these are hard-coded for now but could be added as CLI args if required
    cypress_script = (_parent_dir.parent / Path("node_modules/.bin/cypress")).resolve()

    cypress_glob = "cypress/e2e/**/*.feature"
    cypress_step_definitions = "cypress/e2e/common/**/*.js"
    cypress_filter_specs = "true"

    # clean up URLs in case they have paths, trailing slashes etc.
    parsed_url = urlparse(base_url)
    cypress_base_url = f"{parsed_url.scheme}://{parsed_url.netloc}"

    parsed_url = urlparse(admin_url)
    cypress_admin_url = f"{parsed_url.scheme}://{parsed_url.netloc}"

    # a run is a set of steps in order; a step consists of a set of tags
    # and a user number; each run has a single user number which is
    # used in each step
    runs = []
    for tag_group in tags:
        user_number = generate_user_number()

        run = {
            "user_number": user_number,
            "steps": [],
        }

        for tags in tag_group.split(","):
            run["steps"].append(tags)

        runs.append(run)

    return {
        "script": cypress_script,
        "should_stitch": should_stitch,
        "screenshots_path": _parent_dir / "screenshots",
        "in_ci": in_ci,
        "features_dir": _parent_dir / "e2e",
        "s3_monitor": {
            "verbose": verbose,
        },
        # used to set -e flag passed to cypress, so keys
        # must match those recognised by cypress
        "cypress": {
            "baseUrl": cypress_base_url,
            "adminUrl": cypress_admin_url,
            "GLOB": cypress_glob,
            "filterSpecs": cypress_filter_specs,
            "stepDefinitions": cypress_step_definitions,
        },
        "runs": runs,
    }


if __name__ == "__main__":
    settings = get_settings(sys.argv[1:])

    print("Using settings:")
    print(settings)

    # If not already there, make the cypress screenshots directory.
    # This is because Circle needs to try to copy across screenshots dir after
    # a run and will get upset if it's not there.
    settings["screenshots_path"].mkdir(exist_ok=True, parents=True)

    # stitch scripts together
    if settings["should_stitch"]:
        stitch_feature_files(settings["features_dir"])

    """
    The runs setting looks like this:
    [
        {
            "user_number": "111111",
            "steps": ["@tag1", "@tag2"]
        },
        {
            "user_number": "222222",
            "steps": ["@tag3", "@tag4 or @tag5"]
        }
    ]

    For each run, we use a single user number, and run cypress once
    for each step (step = group of tags specifying features to match), in order.
    This enables us to run a series of steps in a known order with
    a single user.

    Note that runs are not dependent on each other, so could be run
    in parallel if desired.
    """
    num_runs = len(settings["runs"])

    if num_runs == 0:
        print("No runs specified; set some tags with --tags")
        sys.exit(1)

    for run_number, run in enumerate(settings["runs"]):
        run_number += 1
        print(f"Starting run {run_number} (of {num_runs})")

        num_steps = len(run["steps"])

        for step_number, tags in enumerate(run["steps"]):
            step_number += 1
            print(
                f"Starting step {step_number} (of {num_steps}) (run = {run_number}, tags = {tags})"
            )

            options = settings["cypress"]

            options.update(
                {
                    "userNumber": run["user_number"],
                    "CI": settings["in_ci"],
                    "TAGS": tags,
                }
            )

            cypress_command = build_cypress_command(settings["script"], options)
            print(f"cypress command for step:\n{cypress_command}")

            p = Popen(cypress_command, shell=True)
            p.wait()

            if p.returncode != 0:
                print(f"run {run_number}, step {step_number}: FAIL")
                sys.exit(p.returncode)

            print(f"run {run_number}, step {step_number}: OK")

    print("All runs completed successfully")
    sys.exit(0)
