#!/bin/bash

xunitfile=/mnt/test/functional/casperesults.xml
touch $xunitfile

echo The base domain is $BASE_DOMAIN

echo Starting S3 Monitor
php /mnt/test/functional/service/S3Monitor.php &


echo Starting CasperJS Tests

CURRENT_DIR=`pwd`
cd /mnt/test/functional/

CASPER_CMD="/usr/local/bin/casperjs test $* --ignore-ssl-errors=true --ssl-protocol=any --includes=/mnt/test/functional/config/Bootstrap.js --xunit=${xunitfile}"

echo "Running casperjs command line:"
echo $CASPER_CMD
$CASPER_CMD

RETVAL=$?
echo printing $RETVAL

if [ $RETVAL -eq 0 ]; then
    echo OK
else
    echo FAIL
fi

cd $CURRENT_DIR

echo Changing permissions on ${xunitfile}
chmod 777 ${xunitfile}

echo Killing S3 Monitor
kill $(ps aux | grep '[p]hp' | awk '{print $2}')

exit $RETVAL
