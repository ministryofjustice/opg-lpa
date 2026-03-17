#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
grunt --gruntfile Gruntfile.cjs build_js build_css watch
#npm run build:js
