# GOV.UK Pay - pay-integration-php [pre-release]
PHP client for the GOV.UK Pay API

#### PSR-7 HTTP

The Pay PHP Client is based on a PSR-7 HTTP model. You therefore need to pick your preferred HTTP Client library to use.

We will show examples here using the Guzzle v6 Adapter.

## Installing

The Pay PHP Client can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require php-http/guzzle6-adapter alphagov/pay-integration-php
```


## Usage

Assuming you've installed the package via Composer, the Pay PHP Client will be available via the autoloader.

Create a (Guzzle v6 based) instance of the Client using:

```php
$client = new \Alphagov\Pay\Client([
    'apiKey'        => '{your api key}',
    'httpClient'    => new \Http\Adapter\Guzzle6\Client
]);
```

You are then able to access the Pay API using ``$client``.

If you need to access an environment other than production, you can pass the base URL in via the `baseUrl` key in the constructor:

```php
'baseUrl' => '{api base url}'
```

#### Create a Payment

The method signature is:
```php
createPayment( $amount, $reference, $description, UriInterface $returnUrl )
```

Where

* **$amount** A required _int_ holding the payment amount, in pence, in British Pounds (GBP).
* **$reference** A required _string_ holding an application side payment reference.
* **$description** A required _string_ a description of the payment.
* **$returnUrl** A required _Psr\Http\Message\UriInterface_ with the URL the user will be directed back to.

An example request would look like:
```php
try {

    $response = $client->createPayment(
        10 * 100, // £10
        'id-123',
        'Payment for x, y and z.',
        new \GuzzleHttp\Psr7\Uri('https://www.example.service.gov.uk/pay/response')
    );

} catch (PayException $e){}
```

**$response** will be an instance of `Alphagov\Pay\Response\Payment`, which is [documented here](#payment).

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be throw if a Pay error occurs.


#### Lookup a Payment

The method signature is:
```php
getPayment( $payment )
```
Where

* **$payment** is a required _string_ holding the (Pay generated) payment id.

An example request would look like:
```php
try {

    $response = $client->getPayment( 'hu20sqlact5260q2nanm0q8u93' );

} catch (PayException $e){}
```

**$response** will be an instance of `Alphagov\Pay\Response\Payment`, which is [documented here](#payment); **or** `null` if the payment was not found.

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be throw if a Pay error occurs.


## Responses

### Payment
An instance of `Alphagov\Pay\Response\Payment`, which contains the decoded JSON response from the Pay API, representing a single payment.

A full list of returned properties can be found here: https://gds-payments.gelato.io/reference/api/v1

Properties can be accessed directly using the `->` operator. For example:
```php
$response->payment_id
$response->created_date
// etc...
```

If available, the payment page URL to direct the user to is accessible via:
```php
$response->getPaymentPageUrl();
```

This returns either:
* an instance of `Psr\Http\Message\UriInterface` represening the URL; or
* `null` if the URL is unavailable (for example, if the payment is complete).

`Payment` also includes methods for checking the state of the payment:

The payment is _finished_. i.e. the user can no longer interact with the payment page.
```php
$response->isFinished()
```

The payment is _successful_.
```php
$response->isSuccess()
```

All other standard Pay states can also be checked via:
```php
$response->isCreated()
$response->isStarted()
$response->isSubmitted()
$response->isFailed()
$response->isCancelled()
$response->isError()
```

## License

The Pay PHP Client is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE.txt).
