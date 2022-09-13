#!/bin/sh
cd /service-front
export PATH=/app/node_modules/.bin/:$PATH
grunt build_js_dev build_css watch
