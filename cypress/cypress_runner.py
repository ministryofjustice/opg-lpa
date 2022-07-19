import sys
from datetime import datetime
from pathlib import Path
from random import randint
from subprocess import Popen
from threading import Thread

from S3Monitor import S3Monitor


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


# TODO parse command-line args
def get_settings():
    enable_s3_monitor = True
    in_ci = False
    verbose = False

    cypress_tags = "@SignUp,@StitchedPF"

    _parent_dir = Path(__file__).parent

    cypress_script = (
        _parent_dir.parent / Path("node_modules/.bin/cypress-tags")
    ).resolve()

    cypress_glob = (_parent_dir / Path("e2e/**/*.feature")).resolve()

    cypress_base_url = "https://localhost:7002"

    user_number = (
        f"{int(datetime.timestamp(datetime.now()))}{randint(100000000, 999999999)}"
    )

    # a run is a set of cypress tags combined with a user number;
    # this enables tests to be run in sequence where required, e.g.
    # run signup before stitched tests
    runs = []
    for tags in cypress_tags.split(","):
        runs.append({"user_number": user_number, "tags": tags})

    return {
        "script": cypress_script,
        "screenshots_path": _parent_dir / "screenshots",
        "enable_s3_monitor": enable_s3_monitor,
        "s3_monitor": {
            "in_ci": in_ci,
            "verbose": verbose,
        },
        "cypress": {
            "base_url": cypress_base_url,
            "glob": cypress_glob,
        },
        "runs": runs,
    }


if __name__ == "__main__":
    settings = get_settings()

    # If not already there, make the cypress screenshots directory.
    # This is because Circle needs to try to copy across screenshots dir after
    # a run and will get upset if its not there.
    settings["screenshots_path"].mkdir(exist_ok=True, parents=True)

    # start S3Monitor if required (in its own thread)
    if settings["enable_s3_monitor"]:
        monitor = S3Monitor(
            {
                "c": settings["s3_monitor"]["in_ci"],
                "v": settings["s3_monitor"]["verbose"],
            }
        )

        # daemon threads are killed when the main thread exits
        Thread(target=monitor.run, daemon=True).start()

    # TODO stitch scripts together

    # build cypress command
    for run_number, run in enumerate(settings["runs"]):
        run_number += 1

        options = {
            "CYPRESS_baseUrl": settings["cypress"]["base_url"],
            "GLOB": settings["cypress"]["glob"],
            "CYPRESS_userNumber": run["user_number"],
            "TAGS": run["tags"],
        }

        cypress_command = build_cypress_command(settings["script"], options)
        print(f"Will run cypress command:\n{cypress_command}")

        p = Popen(cypress_command, shell=True)
        p.wait()

        if p.returncode != 0:
            print(f"run {run_number}: FAIL")
            sys.exit(p.returncode)

        print(f"run {run_number}: OK")

    sys.exit(0)
