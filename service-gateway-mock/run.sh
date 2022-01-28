#!/bin/sh
node /usr/local/lib/node_modules/@stoplight/prism-cli/dist/index.js mock -h 0.0.0.0 -p 5011 "/app/swagger.yaml"
