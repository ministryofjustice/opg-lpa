<?php

date_default_timezone_set('UTC');

require_once 'vendor/autoload.php';

$id = (string)time();

foreach(glob(__DIR__.'/../test-data/json/*.json') as $filepath) {
    
    $pathInfo = pathinfo(realpath($filepath));
    
    $data = file_get_contents( realpath($filepath) );
    
    foreach(['LPA120', 'LP3', 'LP1'] as $type) {
        if(\file_exists(__DIR__.'/../test-data/output/'.$type.'/'.$type.'-'.$pathInfo['filename'] . '.pdf')) continue;
        \Opg\Lpa\Pdf\Worker\Worker::run( $type.'-'.$pathInfo['filename'], $type, $data );
    }
}

