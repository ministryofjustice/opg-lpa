# This script is intended for manual use, to create a whole batch of test
# users with slightly different email addresses and login tokens. The output is
# SQL which can be appended to the seed_test_users.sql file when required.
#
# An optional <last name> argument can be supplied to set the last name for all
# generated users. This can be useful to mark the purpose of users in the seed
# data.
#
# password for all users is set to Pass1234

import json
import sys

from base64 import b64encode
from datetime import datetime


if len(sys.argv) < 2:
    sys.stderr.write(
        f"Usage: {sys.argv[0]} <number of seed users to create SQL for> <last name (optional)>\n"
    )
    sys.exit(1)

# parse command line
start = 10
end = start + int(sys.argv[1])

lastname = "Benbow"
if len(sys.argv) == 3:
    # script was supplied with a last name for the generated users
    lastname = sys.argv[2]

# the unique part of each record is based on current timestamp
now = f"{datetime.now().timestamp()}"
unique = b64encode(now.encode("utf8")).decode("ascii")[-10:-1].replace("=", "0")

# create users SQL
for num in range(start, end):
    id = f"{unique}{num}a026fa6187fc00b05c"
    token = f"{unique}{num}yYJ7NhXL8IaDC2lLqdDnLtUuteS1T66"
    firstname = f"{unique}{num}"
    email = f"{lastname}{unique}{num}@uat.justice.gov.uk"

    # set even users to active, odd users to inactive
    active = "false"
    activated = "NULL"
    last_login = "NULL"
    if (num % 2) == 0:
        active = "true"
        activated = "'2020-01-22 10:11:53+00'"
        last_login = "'2020-01-23 17:08:02+00'"

    token = {
        "token": token,
        "createdAt": "2020-01-21T15:16:02.000000+0000",
        "expiresAt": "2020-01-21T16:33:58.123695+0000",
        "updatedAt": "2020-01-21T15:18:58.000000+0000",
    }

    token_str = json.dumps(token, indent=None).replace('"', '"')

    profile = {
        "dob": {"date": "1982-11-28T00:00:00.000000+0000"},
        "name": {"last": lastname, "first": firstname, "title": "Mx"},
        "email": {"address": email},
        "address": {
            "address1": "THE OFFICE OF THE PUBLIC GUARDIAN",
            "address2": "THE AXIS",
            "address3": "10 HOLLIDAY STREET, BIRMINGHAM",
            "postcode": "B1 1TF",
        },
    }

    profile_str = json.dumps(profile, indent=None).replace('"', '"')

    sql = f"INSERT INTO public.users (id, identity, password_hash, activation_token, active, failed_login_attempts, created, updated, activated, last_login, last_failed_login, deleted, inactivity_flags, auth_token, email_update_request, password_reset_token, profile) VALUES ('{id}', '{email}', '$2y$10$C9QCpqBK/9xP7x04nUemhO.OvRc.AWCHOb/N0w8Z2SxOMfSnoNI66', NULL, {active}, 0, '2020-01-21 15:15:11.007119+00', '2020-01-21 15:15:53+00', {activated}, {last_login}, NULL, NULL, NULL, '{token_str}', NULL, NULL, '{profile_str}');"

    print(sql)
    print()
