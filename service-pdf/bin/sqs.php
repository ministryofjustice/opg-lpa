<?php

use Opg\Lpa\Pdf\Worker\SqsWorker;

require_once __DIR__ . '/../vendor/autoload.php';

$worker = new SqsWorker();

$worker->start();
