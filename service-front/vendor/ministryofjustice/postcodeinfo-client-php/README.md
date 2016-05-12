# postcodeinfo-client-php

PHP API Client wrapper for [MoJ Postcode Info API](https://github.com/ministryofjustice/postcodeinfo)


#### PSR-7 HTTP

The Postcode Lookup PHP Client is based on a PSR-7 HTTP model. You therefore need to pick your preferred HTTP Client library to use.

We will show examples here using the Guzzle v6 Adapter.


## Installing

The Postcode Lookup PHP Client can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require php-http/guzzle6-adapter ministryofjustice/postcodeinfo-client-php
```

## Usage

### Authentication

You will need an *authentication token* (auth token). If you're using MOJ DS's
Postcode Info server, you can get a token by emailing
platforms@digital.justice.gov.uk with a brief summary of:

* who you are
* what project you're going to be using it on
* roughly how many lookups you expect to do per day

If you're running your own server, see
https://github.com/ministryofjustice/postcodeinfo#auth_tokens for instructions
on how to create a token.

### Instantiation

Assuming you've installed the package via Composer, the Postcode Lookup PHP Client will be available via the autoloader.

Create a (Guzzle v6 based) instance of the Client using:

```php
$client = new \MinistryOfJustice\PostcodeInfo\Client([
    'apiKey'        => '{your api key}',
    'httpClient'    => new \Http\Adapter\Guzzle6\Client
]);
```

You are then able to access the Postcode Lookup API using ``$client``.


# Usage

```php
$postcode = $client->lookupPostcode('AB124YA');

$isValid = $postcode->isValid();

$centrePoint = $postcode->getCentrePoint();
$centrePoint->getLatitude();
$centrePoint->getLongitude();

$localAuth = $postcode->getLocalAuthority();
$localAuth->getName();
$localAuth->getGssCode();

$addresses = $postcode->getAddresses();

foreach ($addresses as $address) {
        $address->getUprn();
        $address->getThoroughfareName();
        $address->getOrganisationName();
        $address->getDepartmentName();
        $address->getPoBoxNumber();
        $address->getBuildingName();
        $address->getSubBuildingName();
        $address->getBuildingNumber();
        $address->getDependentLocality();
        $address->getDoubleDependentLocality();
        $address->getPostTown();
        $address->getPostcode();
        $address->getPostcodeType();
        $address->getFormattedAddress();

        $point = $address->getPoint();
        $point->getLatitude();
        $point->getLongitude();
	}
```

Please see the tests in spec/ClientSpec.php to see all the interface methods and their usage.

# Tests

To run the tests, add a file called spec/api_key. Inside this file place the API token for the postcode info service.

Install the composer dependencies

	php composer.phar install

Then, from the root of the repository

	bin/phpspec run --format=pretty -vvv --stop-on-failure


## License

The Postcode Lookup PHP Client is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE.txt).
