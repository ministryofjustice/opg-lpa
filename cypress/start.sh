#!/bin/bash

echo The base domain is $CYPRESS_baseUrl

echo Starting S3 Monitor
python3 cypress/S3Monitor.py &

# set userNumber env var here at the very start to ensure it applies to all feature files run by Cypress
export CYPRESS_userNumber=`node cypress/userNumber.js`
echo Cypress user number is $CYPRESS_userNumber

echo Starting Cypress Tests

if [[ "$CI" == "true" ]] || [[ "$CYPRESS_headless" == "true" ]] ; then
    # It's CI (used in CircleCI) or headless (local CLI runs)
    # so run the signup test first followed by all others
    echo "Running Cypress headless"
    ./node_modules/.bin/cypress-tags run -e TAGS='@SignUp'
    # Stitch together PF feature files and run
	cp cypress/integration/LpaTypePF.feature cypress/integration/StitchedCreatePFLpa.feature 
	awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
	awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CreatePFLpa.feature >> cypress/integration/StitchedCreatePFLpa.feature 
    cypress run --spec cypress/integration/StitchedCreatePFLpa.feature
    # Stitch together HW feature files and run
	cp cypress/integration/LpaTypeHW.feature cypress/integration/StitchedCreateHWLpa.feature 
	awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
	awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CreateHWLpa.feature >> cypress/integration/StitchedCreateHWLpa.feature 
    cypress run --spec cypress/integration/StitchedCreateHWLpa.feature
    # run remaining tests that haven't already been run
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
