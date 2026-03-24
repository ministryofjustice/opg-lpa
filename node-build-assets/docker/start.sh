#!/bin/sh
cd /service-front
export PATH=/service-front/node_modules/.bin/:$PATH
npm ci --ignore-scripts
npm run build:css
npm run build:js
