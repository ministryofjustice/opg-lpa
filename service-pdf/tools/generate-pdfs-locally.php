<?php

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

use Opg\Lpa\DataModel\Lpa\Lpa;

$id = (string)time();

$worker = new \Opg\Lpa\Pdf\Worker\TestWorker;


foreach (glob(__DIR__ . '/../test-data/json/*.json') as $filepath) {
    $realFilepath = realpath($filepath);
    $pathInfo = pathinfo($realFilepath);
    $fileName = $pathInfo['filename'];


    $data = file_get_contents($realFilepath);
    $lpa = new Lpa($data);

    /*
    * Tests we can generate each PDF, for each expected supported type.
    */

    if ($lpa->canGenerateLP1()) {
        $type = 'LP1';
        $worker->run($type . '-' . $fileName, $type, $data);
    }

    if ($lpa->canGenerateLP3()) {
        $type = 'LP3';
        $worker->run($type . '-' . $fileName, $type, $data);
    }

    if ($lpa->canGenerateLPA120()) {
        $type = 'LPA120';
        $worker->run($type . '-' . $fileName, $type, $data);
    }

    echo PHP_EOL;
}
