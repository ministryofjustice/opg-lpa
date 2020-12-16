#!/bin/bash

echo The base domain is $BASE_DOMAIN

echo Starting S3 Monitor
python3 cypress/S3Monitor.py &


echo Starting Cypress Tests

# pass supplied args to cypress, this would be open (gui) or run (headless) then optionally --project /app
CYPRESS_CMD="cypress $@"

echo "Running cypress command line:"
echo $CYPRESS_CMD
$CYPRESS_CMD

RETVAL=$?
echo printing $RETVAL

if [ $RETVAL -eq 0 ]; then
    echo OK
else
    echo FAIL
fi

exit $RETVAL
