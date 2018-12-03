<?php

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

use Opg\Lpa\DataModel\Lpa\Lpa;
use Aws\Sqs\SqsClient;
use Zend\Filter\Compress;

$client = new SqsClient([
    'region' => 'eu-west-1',
    'version' => '2012-11-05',
]);

foreach (glob(__DIR__ . '/../test-data/json/*.json') as $filepath) {
    $realFilepath = realpath($filepath);
    $pathInfo = pathinfo($realFilepath);
    $fileName = $pathInfo['filename'];


    //$data = file_get_contents($realFilepath);
    $lpa = new Lpa(file_get_contents($realFilepath));

    //---

    /*
    * Tests we can generate each PDF, for each expected supported type.
    */

    if ($lpa->canGenerateLP1()) {
        $type = 'LP1';
        $job = generateJob($lpa, $type);
        postJob($client, $type . '-' . $fileName, $lpa, $job);
    }

    if ($lpa->canGenerateLP3()) {
        $type = 'LP3';
        $job = generateJob($lpa, $type);
        postJob($client, $type . '-' . $fileName, $lpa, $job);
    }

    if ($lpa->canGenerateLPA120()) {
        $type = 'LPA120';
        $job = generateJob($lpa, $type);
        postJob($client, $type . '-' . $fileName, $lpa, $job);
    }

    echo PHP_EOL;
}

function generateJob(Lpa $lpa, $type){
    $message = json_encode([
        'lpa' => $lpa->toArray(),
        'type' => strtoupper($type),
    ]);
    $message = (new Compress('Gz'))->filter($message);
    return base64_encode($message);
}

function postJob($client, $jobId, Lpa $lpa, $message){
    // Add the message to the queue
    $client->sendMessage([
        'QueueUrl' => getenv('OPG_LPA_COMMON_PDF_QUEUE_URL') ?: 'http://localhost:4576/queue/pdf-queue.fifo',
        'MessageBody' => json_encode(
            [
                'jobId' => $jobId,
                'lpaId' => $lpa->getId(),
                'data'  => $message,
            ]
        ),
        'MessageGroupId' => $jobId,
        'MessageDeduplicationId' => $jobId,
    ]);
}
