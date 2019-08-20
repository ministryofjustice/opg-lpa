<?php
require_once __DIR__ . '/../vendor/autoload.php';

$worker = new \Opg\Lpa\Pdf\Worker\SqsWorker();

$worker->start();

