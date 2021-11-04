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
    # so run the signup test first, then stitched, followed by all others, except for Clone which doesn't need a signup as it uses seeded user
    if ! [[ $string =~ "StitchedClone" ]] ; then
        ./node_modules/.bin/cypress-tags run -e TAGS="@SignUp"
    fi

    RETVAL=$?
    if [[ $RETVAL != 0 ]] ; then
        echo FAIL
        exit $RETVAL
    fi

    # stitch feature files and run, to simulate newly signed-up user doing all actions from start to finish
    cypress/stitch.sh

    # if not already there, make the cypress screenshots directory. This is because Circle needs to try to copy across screenshots dir after a run and will get upset if its not there
    mkdir -p cypress/screenshots

    if [ -z "$CYPRESS_TAGS" ]; then
        echo "Error:  CYPRESS_TAGS needs to be set to indicate which tests to run"
        # CYPRESS_TAGS not set to we default to sequentially running StitchedPF, then StitchedHW then the rest
        #./node_modules/.bin/cypress-tags run -e TAGS='@StitchedPF'
        #./node_modules/.bin/cypress-tags run -e TAGS='@StitchedClone'
        #./node_modules/.bin/cypress-tags run -e TAGS='@StitchedHW'
        # run remaining feature files that haven't already been run
        # @CreateLpa is files used in stitching, @StitchedXX is the files resulting from stitching, @SignUp is the SignUp feature
        #./node_modules/.bin/cypress-tags run -e TAGS='not @SignUp and not @CreateLpa and not @CleanupFixtures and not @StitchedHW and not @StitchedPF and not @StitchedClone'
    else
        # CYPRESS_TAGS is set so we run those specific tests
        ./node_modules/.bin/cypress-tags run -e TAGS="$CYPRESS_TAGS"
    fi
else
    echo "Running Cypress"
    # pass supplied args to cypress
    CYPRESS_CMD="cypress $@"
    echo $CYPRESS_CMD
    $CYPRESS_CMD
fi


RETVAL=$?
echo "printing $RETVAL"

if [[ $RETVAL -eq 0 ]]; then
    echo OK
else
    echo FAIL
fi

exit $RETVAL
