import sys
from datetime import datetime
from pathlib import Path
from random import randint
from subprocess import Popen
from threading import Thread

from S3Monitor import S3Monitor


# environment variables to set when calling cypress:
# CYPRESS_baseUrl={cypress_base_url}
# CYPRESS_userNumber={cypress_user_number}
# CYPRESS_TAGS={cypress_tags}
# GLOB={cypress_glob}
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

    # enable this to be set multiple times so that tests can run in parallel?
    cypress_tags = "@SignUp"

    _parent_dir = Path(__file__).parent

    cypress_script = (
        _parent_dir.parent / Path("node_modules/.bin/cypress-tags")
    ).resolve()

    cypress_glob = (_parent_dir / Path("e2e/**/*.feature")).resolve()

    cypress_base_url = "https://localhost:7002"

    # one user per value of cypress_tags?
    user_number = (
        f"{int(datetime.timestamp(datetime.now()))}{randint(100000000, 999999999)}"
    )

    return {
        "script": cypress_script,
        "screenshots_path": _parent_dir / "screenshots",
        "enable_s3_monitor": enable_s3_monitor,
        "s3_monitor": {
            "in_ci": in_ci,
            "verbose": verbose,
        },
        "env": {
            "CYPRESS_userNumber": user_number,
            "CYPRESS_baseUrl": cypress_base_url,
            "GLOB": cypress_glob,
            "TAGS": cypress_tags,
        },
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
    cypress_command = build_cypress_command(settings["script"], settings["env"])
    print(f"Will run cypress command:\n{cypress_command}")

    p = Popen(cypress_command, shell=True)
    p.wait()

    if p.returncode == 0:
        print("OK")
    else:
        print("FAIL")

    sys.exit(p.returncode)
