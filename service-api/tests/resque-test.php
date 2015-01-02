<?php

include "../vendor/autoload.php";

$json = json_encode(json_decode(file_get_contents('data/full.json'), true));

Resque::setBackend('redisback.local:6379');

Resque::enqueue('pdf-queue', '\Opg\Lpa\Pdf\Worker\ResqueWorker', [
    'docId' => (string)time(),
    'type' => 'LP1',
    'lpa' => $json
]);
