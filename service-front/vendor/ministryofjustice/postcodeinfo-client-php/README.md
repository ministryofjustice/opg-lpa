# MoJ DS PostcodeInfo - postcodeinfo-client-php

PHP API Client for the [MoJ DS PostcodeInfo](https://github.com/ministryofjustice/postcodeinfo) service.


#### PSR-7 HTTP

The Postcode Lookup PHP Client is based on a PSR-7 HTTP model. You therefore need to pick your preferred HTTP Client library to use.

We will show examples here using the Guzzle v6 Adapter.

Setup instructions are also available for [Curl](docs/curl-client-setup.md) and [Guzzle v5](docs/guzzle5-client-setup.md).


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


### Return a list of addresses for a postcode

```php
// Return a list of addresses
$addresses = $client->lookupPostcodeAddresses('SW19 5AL');

// Check if any addresses were found
$found = !empty($addresses);

// Count the addresses found
$numberFound = count($addresses);

// Iterate over the returned addresses
foreach ($addresses as $address) {

		// Available properties include...
        $address->uprn;
        $address->organisation_name;
        $address->building_name;
        $address->sub_building_name;
        $address->building_number;
        $address->post_town;
        $address->postcode;
        $address->postcode_type;
        $address->formatted_address;
        $address->thoroughfare_name;
        $address->dependent_locality;
        $address->double_dependent_locality;
        $address->po_box_number;

        // Coordinate info is under...
		$address->point->getLatitude();
        $address->point->getLongitude();

	}
```


### Return metadata about a postcode
```php
$metadata = $client->lookupPostcodeMetadata('AB12 4YA');

// You then have access to the following properties...
$metadata->country->name;
$metadata->country->gss_code;

$metadata->local_authority->name;
$metadata->local_authority->gss_code;

$metadata->centre->getLatitude();
$metadata->centre->getLongitude();

```


## Tests

To run the tests, add a file called spec/api_key. Inside this file place the API token for the postcode info service.

Install the composer dependencies

	php composer.phar install

Then, from the root of the repository

	bin/phpspec run --format=pretty -vvv --stop-on-failure


## License

The Postcode Lookup PHP Client is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE.txt).
