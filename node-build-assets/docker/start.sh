#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
# TODO needs to become watch
#npm run clean
grunt --gruntfile Gruntfile.cjs build_js_dev build_css watch
#npm run build:js
