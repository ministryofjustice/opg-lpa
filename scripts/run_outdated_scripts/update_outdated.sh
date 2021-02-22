#!/usr/bin/env sh
set -euo pipefail

cd ../..
cd tests
composer update --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs
cd ../service-front
composer update --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs
npm update
cd ../service-api
composer update --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs
cd ../service-admin
composer update --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs
cd ../service-pdf
composer update --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs
