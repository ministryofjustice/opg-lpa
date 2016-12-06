<?php

date_default_timezone_set('UTC');

require_once '../../vendor/autoload.php';

use Zend\Barcode\Barcode;

$renderer = Barcode::factory(
    'code39',
    'pdf',
    [
        'text' => 'A11234567891',
        'drawText' => false,
        'factor' => 2,
        'barHeight' => 25,
    ],
    [
        'topOffset' => 789,
        'leftOffset' => 40,
    ]
);

$imageResource = $renderer->draw();

$imageResource->save( 'new.pdf' );

//imagepng( $imageResource, 'out.png' );
