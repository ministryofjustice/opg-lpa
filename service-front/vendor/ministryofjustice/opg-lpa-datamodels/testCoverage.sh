#!/bin/bash

cd tests
../vendor/phpunit/phpunit/phpunit --coverage-html coverage/
open coverage/index.html