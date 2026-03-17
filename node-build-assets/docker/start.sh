#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
# TODO needs to become watch
grunt --gruntfile Gruntfile.cjs build_css watch
#npm run build:js
