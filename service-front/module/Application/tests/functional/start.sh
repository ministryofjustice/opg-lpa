#!/bin/bash

echo Starting CasperJS Tests
/usr/local/bin/casperjs --fail-fast test /mnt/test/$1 --ignore-ssl-errors=true --ssl-protocol=any --includes=/mnt/test/config/Bootstrap.js --xunit=/mnt/test/functional_results.xml
