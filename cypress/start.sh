#!/bin/bash

echo The base domain is $CYPRESS_baseUrl

echo Starting S3 Monitor
python3 cypress/S3Monitor.py &

# set userNumber env var here at the very start to ensure it applies to all feature files run by Cypress
export CYPRESS_userNumber=`node cypress/userNumber.js`
echo Cypress user number is $CYPRESS_userNumber

echo Starting Cypress Tests

if [[ "$CYPRESS_CI" == "true" ]] || [[ "$CYPRESS_headless" == "true" ]] ; then
    echo "Running Cypress headless"
    # It's CI (used in CircleCI) or headless local CLI runs
    # so run the signup test first, then stitched, followed by all others
    ./node_modules/.bin/cypress-tags run -e TAGS='@SignUp'
    # stitch feature files and run, to simulate newly signed-up user doing all actions from start to finish
    cypress/stitch.sh 
    cypress run --spec cypress/integration/StitchedCreatePFLpa.feature
    cypress run --spec cypress/integration/StitchedCreateHWLpa.feature
    # run remaining feature files that haven't already been run 
    # @CreateLpa is all the files that have been stitched, and @SignUp is the SignUp feature
    ./node_modules/.bin/cypress-tags run -e TAGS='not @SignUp and not @CreateLpa'
else
    echo "Running Cypress"
    # pass supplied args to cypress
    CYPRESS_CMD="cypress $@"
    echo $CYPRESS_CMD
    $CYPRESS_CMD
fi


RETVAL=$?
echo printing $RETVAL

if [ $RETVAL -eq 0 ]; then
    echo OK
else
    echo FAIL
fi

exit $RETVAL
