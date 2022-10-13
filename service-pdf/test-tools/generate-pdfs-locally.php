<?php

// Script for testing PDF generation from JSON fixtures.

// Get path to JSON LPA fixture file to load from command line;
// files in tests/fixtures can be used
$filepath = $argv[1];

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

// NOTE: this only runs inside the docker container, if the autoload
// file has been generated inside it; otherwise, this script
// can't find the MakeLogger package. To run it outside the docker
// container, you'll need to regenerate the autoload.php file
// manually first, e.g. with
//
// $ composer dump-autoload

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\PdfRenderer;

$id = (string)time();

$config = Config::getInstance();

$pdfRenderer = new PdfRenderer($config);

$pathInfo = pathinfo($filepath);
$fileName = $pathInfo['filename'];

$data = file_get_contents($filepath);
$lpa = new Lpa($data);

$generatedFiles = [];

if ($lpa->canGenerateLP1()) {
    $type = 'LP1';
    $pdf = $pdfRenderer->render($type . '-' . $fileName, $type, $data);
    $generatedFiles[$type] = $pdf;
}

if ($lpa->canGenerateLP3()) {
    $type = 'LP3';
    $pdf = $pdfRenderer->render($type . '-' . $fileName, $type, $data);
    $generatedFiles[$type] = $pdf;
}

if ($lpa->canGenerateLPA120()) {
    $type = 'LPA120';
    $pdf = $pdfRenderer->render($type . '-' . $fileName, $type, $data);
    $generatedFiles[$type] = $pdf;
}

echo "***************************\n";
echo "GENERATED PDFs:\n";
foreach ($generatedFiles as $type => $pdf) {
    $lpaId = $pdf['lpaId'];
    $filepath = __DIR__ . "/../build/$lpaId-$type.pdf";

    $file = fopen($filepath, "w");
    fwrite($file, $pdf['content']);
    fclose($file);

    echo "    $type: $filepath\n";
}
