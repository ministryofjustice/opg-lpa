<?php

date_default_timezone_set('UTC');

require_once 'vendor/autoload.php';

$id = (string)time();

$worker = new \Opg\Lpa\Pdf\Worker\TestWorker;

foreach(['LPA120', 'LP3', 'LP1'] as $type) {
    foreach(glob(__DIR__.'/../test-data/*.json') as $filepath) {
        $pathInfo = pathinfo(realpath($filepath));
        $data = file_get_contents( realpath($filepath) );
        if(\file_exists(__DIR__.'/../test-data/output/'.$type.'-'.$pathInfo['filename'] . '.pdf')) continue;
        $worker->run( $type.'-'.$pathInfo['filename'], $type, $data );
        echo PHP_EOL;
    }
}

// foreach(glob(__DIR__.'/../test-data/Test*/json/*.json') as $filepath) {
//     $lpaJson = file_get_contents( realpath($filepath) );
//     $lpa = json_decode($lpaJson);
//     $lpa->startedAt = $lpa->createdAt;
//     $lpaJson = json_encode($lpa, JSON_PRETTY_PRINT);
//     file_put_contents($filepath, $lpaJson);
//     echo $filepath."\n";
// }
