#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
grunt build_js_dev build_css watch
