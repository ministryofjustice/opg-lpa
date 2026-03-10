#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
# TODO needs to become watch
grunt build_js_dev build_css
npm run build:js
