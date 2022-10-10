#!/usr/bin/env sh
# This script is used by the Dockerfile to check the health of the container.
# It will check if the PHP-FPM process is is running and ready to serve requests.
# If it is running, it will return 0, otherwise it will return 1.

set -e  # Exit immediately if a command exits with a non-zero status.

# Environment variables used by the cgi-fcgi command
export SCRIPT_NAME=/health
export SCRIPT_FILENAME=/health
export REQUEST_METHOD=GET

if cgi-fcgi -bind -connect 127.0.0.1:9000; then
	exit 0
fi

exit 1