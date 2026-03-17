#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
grunt --gruntfile Gruntfile.cjs build_css 
node ./build.js
