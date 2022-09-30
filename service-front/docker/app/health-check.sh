#!/usr/bin/env sh
set -e

export SCRIPT_NAME=/health
export SCRIPT_FILENAME=/health
export REQUEST_METHOD=GET

if cgi-fcgi -bind -connect 127.0.0.1:9000; then
	exit 0
fi

exit 1