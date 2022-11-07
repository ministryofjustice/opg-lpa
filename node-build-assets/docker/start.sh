#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci
grunt build_js_dev build_css watch
