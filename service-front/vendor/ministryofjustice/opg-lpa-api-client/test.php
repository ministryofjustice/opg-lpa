<?php
include 'vendor/autoload.php';

/*******************************
 * Scratch area for ah-hoc tests
 *******************************/

use Opg\Lpa\Api\Client\Client;

$client = new Client();

$token = $client->registerAccount(uniqid() . '@example.com', 'asdasdasdas12');

echo $token . PHP_EOL;

$result = $client->activateAccount($token);

echo $result . PHP_EOL;

