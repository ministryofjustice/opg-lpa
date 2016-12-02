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

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.


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

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.

#### Cancel a Payment

The method signature is:
```php
cancelPayment( $payment )
```
* **$payment** is a required _string_ holding the (Pay generated) payment id.

An example request would look like:
```php
try {

    $response = $client->cancelPayment( 'hu20sqlact5260q2nanm0q8u93' );

} catch (PayException $e){}
```

**$response** will be bool `true` if the payment was cancelled. Otherwise an instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.


#### Refund a Payment

The method signature is:
```php
refundPayment( $payment, $amount, $refundAmountAvailable = null )
```

* **$payment** is a required _string_ holding the (Pay generated) payment id.
* **$amount** A required _int_ holding the amount to be refunded, in pence, in British Pounds (GBP).
* **$refundAmountAvailable** An optional _int_ holding the expected amount available for refund, in pence, in British Pounds (GBP).

An example request would look like:
```php
try {

    $response = $client->refundPayment( 
    	'hu20sqlact5260q2nanm0q8u93', 
        10 * 100, // £10
        50 * 100  // £50
    );

} catch (PayException $e){}
```

**$response** will be an instance of `Alphagov\Pay\Response\Refund`, which is [documented here](#refund).

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.


#### Lookup all Refunds for a Payment

The method signature is:
```php
getPaymentRefunds( $payment )
```
Where

* **$payment** is a required _string_ holding the (Pay generated) payment id.

An example request would look like:
```php
try {

    $response = $client->getPaymentRefunds( 'hu20sqlact5260q2nanm0q8u93' );

} catch (PayException $e){}
```

**$response** will be an instance of `Alphagov\Pay\Response\Refunds`, which will contain an instance of `Alphagov\Pay\Response\Refund` ([docs](#refund)) for each refund processed.

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.


#### Lookup a single Refund for a Payment

The method signature is:
```php
getPaymentRefund( $payment, $refund )
```
Where

* **$payment** is a required _string_ holding the (Pay generated) payment id.
* **$refund** is a required _string_ holding the (Pay generated) refund id.

An example request would look like:
```php
try {

    $response = $client->getPaymentRefunds( 
      'hu20sqlact5260q2nanm0q8u93', 
      'j2cg5v7et0424d7shtrt7r0mj'
    );

} catch (PayException $e){}
```

**$response** will be an instance of `Alphagov\Pay\Response\Refund` ([docs](#refund)), or NULL if the refund is not found.

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.

#### Lookup all Events for a Payment

The method signature is:
```php
getPaymentEvents( $payment )
```
Where

* **$payment** is a required _string_ holding the (Pay generated) payment id.

An example request would look like:
```php
try {

    $response = $client->getPaymentEvents( 'hu20sqlact5260q2nanm0q8u93' );

} catch (PayException $e){}
```

**$response** will be an instance of `Alphagov\Pay\Response\Events`, which will contain an instance of `Alphagov\Pay\Response\Event` ([docs](#event)) for each event.

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.

#### Search Payments

The method signature is:
```php
searchPayments( array $filters = array() )
```
Where

* **$filters** An optional _array_ which applies filters to the request. Zero or more filters can be used. Supported filtered:
    * ``reference``
    * ``state``
    * ``from_date``
    * ``to_date``
    * ``page``
    * ``display_size``

Full filter details can be found here: https://gds-payments.gelato.io/docs/versions/1.0.0/resources/general/endpoints/search-payments

An example request would look like:

```php
try {

    $response = $client->searchPayments([
    	'from_date' => '2015-08-14T12:35:00Z',
        'page' => '2',
        'display_size' => '50'
    ]);

} catch (NotifyException $e){}
```

**$response** will be an instance of `Alphagov\Pay\Response\Payments`, which will contain an instance of `Alphagov\Pay\Response\Payment` ([docs](#payment)) for each payment found.

An instance (or sub-class) of ``Alphagov\Pay\Exception\PayException`` will be thrown if a Pay error occurs.


## Responses

All Response objects except Event have a `getResponse()` which returns a class implementing `Psr\Http\Message\ResponseInterface`, containing the original API response.

### Payment
An instance of `Alphagov\Pay\Response\Payment`, which contains the decoded JSON response from the Pay API, representing a single payment.

A full list of returned properties can be found here: https://gds-payments.gelato.io/docs/versions/1.0.0/resources/payment-id/endpoints/find-payment-by-id

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

### Refund
An instance of `Alphagov\Pay\Response\Refund`, which contains the decoded JSON response from the Pay API, representing a single refund.

A full list of returned properties can be found here: https://gds-payments.gelato.io/docs/versions/1.0.0/resources/payment-id/endpoints/find-payment-refund-by-id

Properties can be accessed directly using the `->` operator. For example:
```php
$response->refund_id
$response->status
$response->amount
// etc...
```

### Event
An instance of `Alphagov\Pay\Response\Event`, which contains the decoded JSON response from the Pay API, representing a single event.

A full list of returned properties can be found here: https://gds-payments.gelato.io/docs/versions/1.0.0/resources/payment-id/endpoints/return-payment-events-by-id

Properties can be accessed directly using the `->` operator. For example:
```php
$response->state
$response->updated
// etc...
```

### Payments, Refunds & Events
All three of these responses represent collections of their respective response type. They all extend PHP’s `ArrayObject`, thus can be treated as an array.

Both Refunds & Events also support the addition `$response->payment_id` property, containing the payment ID to which they relate.


## License

The Pay PHP Client is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE.txt).
