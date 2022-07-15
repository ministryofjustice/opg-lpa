#!/bin/sh

cd /app

seedData="${OPG_LPA_SEED_DATA:-false}"

COUNTER=0

while : ; do

    ./vendor/bin/laminas service-api:lock \
    --table $OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE \
    --name "$OPG_LPA_STACK_NAME/db-migrate" \
    --ttl 60 \
    --endpoint $OPG_LPA_COMMON_DYNAMODB_ENDPOINT

    retval=$?

    if [ $retval -eq 0 ]; then
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
        break
    elif [ $retval -eq 1 ]; then
        # Lock not acquired
        break
    else
        let COUNTER=COUNTER+1

        if [ $COUNTER -gt 10 ]; then
            echo "Fatal error: Unable to attempt migrations"
            exit 1
        fi

        echo "Error with lock system; will re-try"
        sleep 2
    fi

done

exit 0
