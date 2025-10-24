#!/bin/sh

cd /app

COUNTER=0

while : ; do

    ./vendor/bin/laminas service-api:lock \
    --table $OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE \
    --name "$OPG_LPA_STACK_NAME/db-migrate" \
    --ttl 60 \
    --endpoint $OPG_LPA_COMMON_DYNAMODB_ENDPOINT

    retval=$?

    if [ $retval -eq 0 ]
    then
        # Acquired lock
        echo "Waiting for postgres to be ready before running migrations"

        # wait until code is 0 (all migrations applied), 2 (one or more up migrations
        # need to be applied) or 3 (one or more down migrations to be applied)
        # see https://book.cakephp.org/phinx/0/en/commands.html#the-status-command
        timeout 600s sh -c 'pgready=1; until [[ ${pgready} -eq 0 || ${pgready} -eq 2 || ${pgready} -eq 3 ]]; do vendor/robmorgan/phinx/bin/phinx status ; pgready=$? ; echo "pgready = $pgready" ; sleep 15 ; done'

        vendor/robmorgan/phinx/bin/phinx status > /dev/null
        database_ready="$?"

        if [[ $database_ready = "0" || $database_ready = "2" || $database_ready = "3" ]]
        then
            echo "Migrating API data to postgres db via phinx"
            vendor/robmorgan/phinx/bin/phinx migrate

            retval=0
        else
            echo "ERROR: Database is not ready for API data migration via phinx"
            retval=1
        fi

        break
    elif [ $retval -eq 1 ]; then
        # Lock not acquired
        break
    else
        let COUNTER=COUNTER+1

        if [ $COUNTER -gt 10 ]
        then
            echo "Fatal error: Unable to attempt migrations"
            exit 1
        fi

        echo "Error with lock system; will re-try"
        sleep 2
    fi
done

exit $retval
