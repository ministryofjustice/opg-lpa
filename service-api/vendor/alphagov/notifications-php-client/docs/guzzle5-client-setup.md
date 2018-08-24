# GOV.UK Notify - PHP Client with Guzzle v5

#### PSR-7 HTTP

The Notify PHP Client is based on a PSR-7 HTTP model. You therefore need to pick your preferred HTTP Client library to use.

This shows how to setup the GOV.UK Notify using Guzzle v5.

## Installing

The Notify PHP Client can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require php-http/guzzle5-adapter php-http/message alphagov/notifications-php-client
```

## Usage

Assuming you've installed the package via Composer, the Notify PHP Client will be available via the autoloader.

Create a Guzzle v5 client based instance of the Client using:

```php
$notifyClient = new \Alphagov\Notifications\Client([
    'serviceId'     => '{your service id}',
    'apiKey'        => '{your api key}',
    'httpClient'    => new \Http\Adapter\Guzzle5\Client(
        new \GuzzleHttp\Client,
        new \Http\Message\MessageFactory\GuzzleMessageFactory
    ),
]);
```

You are then able to access the Notify API using ``$notifyClient``.

If you need to access an environment other than production, you can pass the base URL in via the `baseUrl` key in the constructor:

```php
'baseUrl' => '{api base url}'
```

All remaining usage is as per the [README](../README.md).