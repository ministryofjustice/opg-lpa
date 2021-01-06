#!/bin/bash

echo The base domain is $BASE_DOMAIN

echo Starting S3 Monitor
python3 cypress/S3Monitor.py &

# set userNumber env var here at the very start to ensure it applies to all feature files run by Cypress
export CYPRESS_userNumber=`node cypress/userNumber.js`
echo Cypress user number is $CYPRESS_userNumber

echo Starting Cypress Tests

# pass supplied args to cypress, this would be open (gui) or run (headless) then optionally --project /app
CYPRESS_CMD="cypress $@"

# see if we asked for a GUI by specifying "open"  . 
for i in "$@" ; do
    if [[ $i == "open" ]] ; then
        GUI=true
        break
    fi
done

if [[ $GUI == "true" ]] ; then
    # Its a GUI, so simply open up 
    echo "Running Cypress GUI"
    echo $CYPRESS_CMD
    $CYPRESS_CMD
else
    # Its headless so run the signup test first followed by all others
    echo "Running Cypress headless"
    echo $CYPRESS_CMD
    $CYPRESS_CMD --spec cypress/integration/Signup.feature
    # todo: this is not a beautiful way to do this, better would be to use cypress-tags combined with tags in feature files, but that does not seem to work
    $CYPRESS_CMD --spec `ls cypress/integration/*.feature | grep -v Signup | tr '\n' , | rev | cut -c 2- | rev`
fi


RETVAL=$?
echo printing $RETVAL

if [ $RETVAL -eq 0 ]; then
    echo OK
else
    echo FAIL
fi

exit $RETVAL
