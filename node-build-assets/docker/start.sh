#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
# NOTE watch will be re-instated once css no longer built by grunt
grunt --gruntfile Gruntfile.cjs build_js watch
npm run build:css
