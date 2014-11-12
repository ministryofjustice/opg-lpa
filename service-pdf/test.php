<?php

date_default_timezone_set('UTC');

require_once 'vendor/autoload.php';

$id = (string)time();

\Opg\Lpa\Pdf\Worker\Worker::run( $id, 'LP1H', 'xxx' );
