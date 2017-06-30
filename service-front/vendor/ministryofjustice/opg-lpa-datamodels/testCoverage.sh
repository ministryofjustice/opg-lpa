#!/bin/bash

cd tests
phpunit --coverage-html coverage/
open coverage/index.html