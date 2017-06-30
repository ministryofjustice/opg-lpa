<?php

date_default_timezone_set('UTC');
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', true);
ini_set('log_errors', false);

//  Autoload the vendor components
$vendorAutoloadFile = __DIR__ . '/../vendor/autoload.php';

if (file_exists($vendorAutoloadFile)) {
    require $vendorAutoloadFile;
}
