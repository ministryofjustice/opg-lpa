<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists(\Laminas\Http\Response::class)) {
    require_once __DIR__ . '/Stubs/LaminasHttpResponseStub.php';
}
