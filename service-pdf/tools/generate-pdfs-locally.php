<?php

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

$config = Config::getInstance([
    'service' => [
        'assets' => [
            'source_template_path' => __DIR__ . '/../assets/v2/',
            'template_path_on_ram_disk' => __DIR__ . '/../build/pdf-templates',
            'intermediate_file_path' => __DIR__ . '/../build'
        ],
    ],
    'pdf' => [
        'password' => 'default-password'
    ],
]);

$pdfRenderer = new PdfRenderer($config);

$filepath = __DIR__ . '/../tests/fixtures/lpa-pf.json';

$pathInfo = pathinfo($filepath);
$fileName = $pathInfo['filename'];

$data = file_get_contents($filepath);
$lpa = new Lpa($data);

$generatedFiles = [];

if ($lpa->canGenerateLP1()) {
    $type = 'LP1';
    $filepath = $pdfRenderer->render($type . '-' . $fileName, $type, $data);
    $generatedFiles[$type] = $filepath;
}

if ($lpa->canGenerateLP3()) {
    $type = 'LP3';
    $filepath = $pdfRenderer->render($type . '-' . $fileName, $type, $data);
    $generatedFiles[$type] = $filepath;
}

if ($lpa->canGenerateLPA120()) {
    $type = 'LPA120';
    $filepath = $pdfRenderer->render($type . '-' . $fileName, $type, $data);
    $generatedFiles[$type] = $filepath;
}

echo "***************************\n";
echo "GENERATED PDFs:\n";
foreach ($generatedFiles as $type => $filepath) {
    echo "    $type: $filepath\n";
}
