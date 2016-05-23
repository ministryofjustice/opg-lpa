# MoJ DS PostcodeInfo - PHP Client with Curl

#### PSR-7 HTTP

The Postcode Lookup PHP Client is based on a PSR-7 HTTP model. You therefore need to pick your preferred HTTP Client library to use.

This shows how to setup the client using Curl.

## Installing

The Postcode Lookup PHP Client can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require php-http/curl-client php-http/message ministryofjustice/postcodeinfo-client-php
```

## Usage

Assuming you've installed the package via Composer, client will be available via the autoloader.

Create a Curl client based instance of the Client using:

```php
$client = new \MinistryOfJustice\PostcodeInfo\Client([
    'apiKey'        => '{your api key}',
    'httpClient'    => new \Http\Client\Curl\Client(
        new \Http\Message\MessageFactory\GuzzleMessageFactory,
        new \Http\Message\StreamFactory\GuzzleStreamFactory
    ),
]);
```

You are then able to access the API using ``$client``.

If you need to access an environment other than production, you can pass the base URL in via the `baseUrl` key in the constructor:

```php
'baseUrl' => '{api base url}'
```

All remaining usage is as per the [README](../README.md).