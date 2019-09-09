#!/bin/bash

xunitfile=/mnt/test/casperesults.xml

echo Starting Email Monitor
php /mnt/test/service/EmailMonitor.php &

# echo Removing ${xunitfile}
# rm -f ${xunitfile}

echo Starting CasperJS Tests
/usr/local/bin/casperjs test /mnt/test/$1 --ignore-ssl-errors=true --ssl-protocol=any --includes=/mnt/test/config/Bootstrap.js  --xunit=${xunitfile}

echo Changing permissions on ${xunitfile}
# We are running as root so make xunit results deletable
# in the local mapped filesystem.
chmod 777 ${xunitfile}

RETVAL=$?

echo Killing Email Monitor
killall php

exit ${RETVAL}
