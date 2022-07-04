<?php

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

// NOTE: this only runs inside the docker container, if the autoload
// file has been generated inside it; otherwise, this script
// can't find the MakeLogger package. To run it outside the docker
// container, you'll need to regenerate the autoload.php file
// manually, e.g.
// composer dump-autoload

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\PdfRenderer;

$id = (string)time();

$pdfRenderer = new PdfRenderer([
    'source_template_path' => __DIR__ . '/../assets/v2/',
    'template_path_on_ram_disk' => '/tmp/pdf_cache/assets/v2/',
]);

$filepath = __DIR__ . '/../tests/fixtures/lpa-pf.json';

$pathInfo = pathinfo($filepath);
$fileName = $pathInfo['filename'];

$data = file_get_contents($filepath);
$lpa = new Lpa($data);

/*
* Tests we can generate each PDF, for each expected supported type.
*/
if ($lpa->canGenerateLP1()) {
    $type = 'LP1';
    $pdfRenderer->render($type . '-' . $fileName, $type, $data);
}

if ($lpa->canGenerateLP3()) {
    $type = 'LP3';
    $pdfRenderer->render($type . '-' . $fileName, $type, $data);
}

if ($lpa->canGenerateLPA120()) {
    $type = 'LPA120';
    $pdfRenderer->render($type . '-' . $fileName, $type, $data);
}

echo PHP_EOL;
