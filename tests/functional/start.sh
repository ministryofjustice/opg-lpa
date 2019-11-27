#!/bin/bash

whoami

xunitfile=/mnt/test/casperesults.xml

echo The base domain is $BASE_DOMAIN

echo Starting S3 Monitor
php /mnt/test/functional/service/S3Monitor.php &

# echo Removing ${xunitfile}
# rm -f ${xunitfile}

echo Starting CasperJS Tests
/usr/local/bin/casperjs test /mnt/test/functional/$1 --ignore-ssl-errors=true --ssl-protocol=any --includes=/mnt/test/functional/config/Bootstrap.js  --xunit=${xunitfile}

if [ $? -eq 0 ]; then
    echo OK
else
    echo FAILLLLLL
    exit 1
fi

echo Changing permissions on ${xunitfile}
# We are running as root so make xunit results deletable
# in the local mapped filesystem.
chmod 777 ${xunitfile}

RETVAL=$?

echo printing RETVAL


echo Killing S3 Monitor
kill $(ps aux | grep '[p]hp' | awk '{print $2}')
#killall php

exit ${RETVAL}
