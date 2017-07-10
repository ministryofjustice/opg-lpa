#!/bin/bash

cd module/Application/tests
phpunit --coverage-html module/Application/tests/coverage/
open module/Application/tests/coverage/index.html