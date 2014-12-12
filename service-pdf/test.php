<?php

date_default_timezone_set('UTC');

require_once 'vendor/autoload.php';

$id = (string)time();

$data = file_get_contents( 'test-data/test-1.json' );

\Opg\Lpa\Pdf\Worker\Worker::run( $id, 'LP1', $data );
