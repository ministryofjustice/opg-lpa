<?php

date_default_timezone_set('UTC');

require_once 'vendor/autoload.php';

$id = (string)time();

$data = file_get_contents( 'test-data/test-1.json' );

(new \Opg\Lpa\Pdf\Worker\TestWorker)->run( $id, 'LP1', $data );
