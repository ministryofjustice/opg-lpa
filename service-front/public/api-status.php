<?php

$url = getenv('OPG_LPA_ENDPOINTS_API') . '/fpm-status?json';

$cURLConnection = curl_init();

curl_setopt($cURLConnection, CURLOPT_URL, $url);
curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

$json = curl_exec($cURLConnection);
curl_close($cURLConnection);

$jsonArrayResponse - json_decode($json);

echo $jsonArrayResponse;
