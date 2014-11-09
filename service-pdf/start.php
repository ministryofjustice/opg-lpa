<?php

date_default_timezone_set('UTC');

require_once 'vendor/autoload.php';

$lpa = new \Opg\Lpa\DataModel\Lpa\Lpa();

var_dump( $lpa ); exit();

\Opg\Lpa\Pdf\Worker\Worker::run( 'xxx', 'xxx', 'xxx' );
