#!/usr/bin/env python3
import argparse
import os
import pathlib

from datetime import datetime
from sys import exit

from tests.helpers import DEFAULT_LOAD_TEST_CONFIG_FILE_PATH


parser = argparse.ArgumentParser(description='Run locust load tests')
parser.add_argument('test_suite', nargs=1, help='Test suite to run (default: %(default)s')
parser.add_argument('--env', dest='test_env',
    help='Environment to load test against (default: %(default)s)',
    choices=['local'], default='local')
parser.add_argument('--config', dest='config_file_path',
    help='JSON file to load configuration from',
    default=DEFAULT_LOAD_TEST_CONFIG_FILE_PATH)
parser.add_argument('--clients', dest='clients',
    help='Number of clients to spawn (default: %(default)s)', default=10)
parser.add_argument('--spawn', dest='spawn',
    help='Client spawn rate per second (default: %(default)s client(s) per second)',
    default=1)
parser.add_argument('--logs', dest='logs',
    help='Directory to write logs to (default: %(default)s)',
    default=pathlib.Path('build', 'load_tests'))
parser.add_argument('--run-time', dest='run_time',
    help='Length of time to run, e.g. "30m" (default: %(default)s)', default=None)
args = parser.parse_args()

test_suite = args.test_suite[0]

log_dir = pathlib.Path(args.logs)
log_dir.mkdir(parents=True, exist_ok=True)
logfile_prefix = log_dir.joinpath(f'load_tests_{datetime.now().isoformat()}')

command = f"""LOAD_TEST_CONFIG_FILE_PATH={args.config_file_path} \
    LOAD_TEST_ENV={args.test_env} locust -f {test_suite} \
    --headless -u {args.clients} -r {args.spawn} --csv {logfile_prefix}"""

if args.run_time is not None:
    command = f'{command} --run-time {args.run_time}'

print(f'Running load tests against {args.test_env} environment')
print(f'Logging {logfile_prefix}*.csv')
print(command)
exit_code = os.WEXITSTATUS(os.system(command))
exit(exit_code)
