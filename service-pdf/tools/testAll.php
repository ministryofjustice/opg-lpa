<?php

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

$id = (string)time();

$worker = new \Opg\Lpa\Pdf\Worker\TestWorker;

foreach (['LPA120', 'LP3', 'LP1'] as $type) {
    foreach (glob(__DIR__ . '/../test-data/*.json') as $filepath) {
        $realFilepath = realpath($filepath);
        $pathInfo = pathinfo($realFilepath);
        $fileName = $pathInfo['filename'];

        //  Check to see if an output file already exists
        if (\file_exists(__DIR__ . '/../test-data/output/' . $type . '-' . $fileName . '.pdf')) {
            continue;
        }

        //  An output file doesn't exist so create one now
        $worker->run($type . '-' . $fileName, $type, file_get_contents($realFilepath));

        echo PHP_EOL;
    }
}
