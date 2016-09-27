<?php

date_default_timezone_set('UTC');

require_once '../../vendor/autoload.php';

use Zend\Barcode\Barcode;

// Only the text to draw is required.
$barcodeOptions = [
    'text' => 'A11234567891',
    'drawText' => false,
    'factor' => 2,
    'barHeight' => 25,
];

// No required options.
$rendererOptions = [
    //'horizontalPosition' => 'center',
    'topOffset' => 787,
    'leftOffset' => 40,
];

$renderer = Barcode::factory(
    'code39',
    'pdf',
    $barcodeOptions,
    $rendererOptions
);

$imageResource = $renderer->draw();

$imageResource->save( 'new.pdf' );

//imagepng( $imageResource, 'out.png' );
