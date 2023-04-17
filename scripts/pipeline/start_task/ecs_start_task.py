import argparse
import sys

from ECSMonitor import *


def main():

    print(sys.path)
    parser = argparse.ArgumentParser(
        description=f"Start the task for the Make an LPA database"
    )

    parser.add_argument(
        "config_file_path",
        nargs="?",
        default="/tmp/environment_pipeline_tasks_config.json",
        type=str,
        help="Path to config file produced by terraform",
    )

    parser.add_argument(
        "--task_name",
        nargs="?",
        type=str,
        help="Name of AWS task that we want to start",
    )
    args = parser.parse_args()

    work = ECSMonitor(args.config_file_path, args.task_name)
    work.run_task()
    work.wait_for_task_to_start()
    work.print_task_logs()

    # at this point, the task has finished: see print_task_logs() where
    # we check for this

    # get the task exit code and use this as the exit code for this script
    return work.get_task_exit_code()


if __name__ == "__main__":
    sys.exit(main())
