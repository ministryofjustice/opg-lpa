#!/bin/bash

xunitfile=/mnt/test/casperesults.xml

echo The base domain is $BASE_DOMAIN

echo Starting S3 Monitor
php /mnt/test/functional/service/S3Monitor.php &


echo Starting CasperJS Tests
/usr/local/bin/casperjs test /mnt/test/functional/$1 --ignore-ssl-errors=true --ssl-protocol=any --includes=/mnt/test/functional/config/Bootstrap.js  --xunit=${xunitfile}

RETVAL=$?
echo printing $RETVAL

if [ $RETVAL -eq 0 ]; then
    echo OK
else
    echo FAIL
fi

echo Changing permissions on ${xunitfile}
chmod 777 ${xunitfile}

echo Killing S3 Monitor
kill $(ps aux | grep '[p]hp' | awk '{print $2}')

exit $RETVAL
