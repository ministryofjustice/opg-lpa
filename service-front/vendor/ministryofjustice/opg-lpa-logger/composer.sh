#!/bin/bash
php composer.phar self-update
php composer.phar update --prefer-dist --optimize-autoloader

