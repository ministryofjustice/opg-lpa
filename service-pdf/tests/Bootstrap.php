<?php
date_default_timezone_set('Europe/London');
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', true);
ini_set('log_errors', false);

if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
}
