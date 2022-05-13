#!/bin/sh

cd /app

seedData="${OPG_LPA_SEED_DATA:-false}"

while : ; do
    # Acquired lock
    echo "Waiting for postgres to be ready before running migrations"

    # wait until code is 0 (all migrations applied), 2 (one or more migrations
    # missing) or 3 (one or more migrations down)
    # see https://phinx.readthedocs.io/en/latest/commands.html#the-status-command
    timeout 90s sh -c 'pgready=1; until [[ ${pgready} -eq 0 || ${pgready} -eq 2 || ${pgready} -eq 3 ]]; do vendor/robmorgan/phinx/bin/phinx status ; pgready=$? ; echo "pgready = $pgready" ; sleep 5 ; done'

    echo "Migrating API data to postgres db via phinx"
    vendor/robmorgan/phinx/bin/phinx migrate
    if ${seedData}; then
        vendor/robmorgan/phinx/bin/phinx seed:run
    fi
done

exit 0
