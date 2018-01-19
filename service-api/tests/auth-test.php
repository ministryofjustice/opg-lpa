<?php


include "../vendor/autoload.php";

$client = new \GuzzleHttp\Client();

$response = $client->get('http://auth.local/tokeninfo', [ 'query' => ['access_token'=>'d172b870aeaf8822facdce36b25c5752'] ] );

if( $response->getStatusCode() == 200 ){
    var_export(json_decode($response->getBody()), true);
}

