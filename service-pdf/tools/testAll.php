<?php

date_default_timezone_set('UTC');

require_once 'vendor/autoload.php';

$id = (string)time();

foreach(['LPA120', 'LP3', 'LP1'] as $type) {
    foreach(glob(__DIR__.'/../test-data/json/*.json') as $filepath) {
    
        $pathInfo = pathinfo(realpath($filepath));
        $data = file_get_contents( realpath($filepath) );
    
        if(\file_exists(__DIR__.'/../test-data/output/'.$type.'-'.$pathInfo['filename'] . '.pdf')) continue;
        \Opg\Lpa\Pdf\Worker\Worker::run( $type.'-'.$pathInfo['filename'], $type, $data );
        echo PHP_EOL;
    }
}

