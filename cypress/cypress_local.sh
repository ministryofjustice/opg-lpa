#!/bin/sh
# Script to run make cypress-local on dev machine;
# runs signup, then the stitched pf and hw features
python3 ./cypress/cypress_runner.py -u https://localhost:7002 "@SignUp,@StitchedPF or @StitchedHW"
