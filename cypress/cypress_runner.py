import sys
from argparse import ArgumentParser
from datetime import datetime
from pathlib import Path
from random import randint
from subprocess import Popen
from threading import Thread
from urllib.parse import urlparse

from s3_monitor import S3Monitor
from stitch import stitch_feature_files


def build_cypress_command(command, env={}):
    """
    :param command: path to script to run cypress
    :param env: dict in format:
        {
            "CYPRESS_userNumber": <CYPRESS_userNumber>,
            "CYPRESS_baseUrl": <CYPRESS_baseUrl>,
            "GLOB": <GLOB for feature files>
            "TAGS": <CYPRESS_TAGS setting>
        }
    """
    command = f"{command} run --headless --config video=false"

    if len(env) > 0:
        e_vars = " ".join(
            [
                f'{key}="{value}"'
                for key, value in env.items()
                if key in ["GLOB", "TAGS"]
            ]
        )
        if len(e_vars) > 0:
            command = f"{command} -e {e_vars}"

        env_vars = " ".join(
            [
                f'{key}="{value}"'
                for key, value in env.items()
                if key in ["CYPRESS_baseUrl", "CYPRESS_userNumber"]
            ]
        )
        if len(env_vars) > 0:
            command = f"{env_vars} {command}"

    return command


def get_settings():
    _parent_dir = Path(__file__).parent

    parser = ArgumentParser(description="Run cypress features")
    parser.add_argument("runs", nargs="+")
    parser.add_argument(
        "-d",
        "--disable-s3-monitor",
        action="store_true",
        help="set to disable the S3 monitor",
    )
    parser.add_argument(
        "-n",
        "--no-stitch",
        action="store_true",
        help="set to disable stitching feature files together",
    )
    parser.add_argument(
        "-c",
        "--in-ci",
        action="store_true",
        help="set if running S3 monitor in the CI environment",
    )
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="set to make S3 monitor provide verbose output",
    )
    parser.add_argument(
        "-u",
        "--base-url",
        default="https://localhost:7002",
        help="base URL of the front end (path is ignored)",
    )
    args = parser.parse_args()

    disable_s3_monitor = args.disable_s3_monitor
    in_ci = args.in_ci
    verbose = args.verbose
    should_stitch = not args.no_stitch

    parsed_url = urlparse(args.base_url)
    cypress_base_url = f"{parsed_url.scheme}://{parsed_url.netloc}"

    # these are hard-coded for now but could be added as CLI args if required
    cypress_script = (
        _parent_dir.parent / Path("node_modules/.bin/cypress-tags")
    ).resolve()

    cypress_glob = (_parent_dir / Path("e2e/**/*.feature")).resolve()

    user_number = (
        f"{int(datetime.timestamp(datetime.now()))}{randint(100000000, 999999999)}"
    )

    # a run is a set of steps in order; a step consists of a set of tags
    # and a user number; each run has a single user number which is
    # used in each step
    runs = []
    for tag_group in args.runs:
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
        "disable_s3_monitor": disable_s3_monitor,
        "s3_monitor": {
            "in_ci": in_ci,
            "verbose": verbose,
        },
        "cypress": {
            "features_dir": _parent_dir / "e2e",
            "base_url": cypress_base_url,
            "glob": cypress_glob,
        },
        "runs": runs,
    }


if __name__ == "__main__":
    settings = get_settings()

    # If not already there, make the cypress screenshots directory.
    # This is because Circle needs to try to copy across screenshots dir after
    # a run and will get upset if it's not there.
    settings["screenshots_path"].mkdir(exist_ok=True, parents=True)

    # start S3Monitor if required (in its own thread)
    if not settings["disable_s3_monitor"]:
        monitor = S3Monitor(
            {
                "c": settings["s3_monitor"]["in_ci"],
                "v": settings["s3_monitor"]["verbose"],
            }
        )

        # daemon threads are killed when the main thread exits
        Thread(target=monitor.run, daemon=True).start()

    # stitch scripts together
    if settings["should_stitch"]:
        stitch_feature_files(settings["cypress"]["features_dir"])

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
    for run_number, run in enumerate(settings["runs"]):
        run_number += 1
        print(f"Starting run {run_number} (of {num_runs})")

        num_steps = len(run["steps"])

        for step_number, tags in enumerate(run["steps"]):
            step_number += 1
            print(
                f"Starting step {step_number} (of {num_steps}) (run = {run_number}, tags = {tags})"
            )

            options = {
                "CYPRESS_baseUrl": settings["cypress"]["base_url"],
                "GLOB": settings["cypress"]["glob"],
                "CYPRESS_userNumber": run["user_number"],
                "TAGS": tags,
            }

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
