# postcodeinfo-client-php

PHP API Client wrapper for [MoJ Postcode Info API](https://github.com/ministryofjustice/postcodeinfo)

# Installation

Update your composer.json file to include:

    "require": {
        "ministryofjustice/postcodeinfo-client-php": "*"
    },
    
Then install the composer dependencies.

	php composer.phar install
	
If you are unfamiliar with Composer and don't have the composer.phar file, please [see the Composer docs here](https://getcomposer.org/download/).

If you are not using a PHP framework that handles autoloading for you, you will need to include vendor/autoload.php at the top of any script that uses the PostcodeInfo client classes.

# Usage

Authentication
--------------

You will need an *authentication token* (auth token). If you're using MOJ DS's
Postcode Info server, you can get a token by emailing
platforms@digital.justice.gov.uk with a brief summary of:

* who you are
* what project you're going to be using it on
* roughly how many lookups you expect to do per day

If you're running your own server, see
https://github.com/ministryofjustice/postcodeinfo#auth_tokens for instructions
on how to create a token.

Quick Start
-----------

	use MinistryOfJustice\PostcodeInfo\Client\Client;

	$client = new Client(
		'API_KEY_HERE',
		'https://postcodeinfo-staging.dsd.io'
	);
	
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

Please see the tests in spec/ministryofjustice/postcodeinfo/client/ClientSpec.php to see all the interface methods and their usage.

# Tests

To run the tests, add a file called spec/api_key. Inside this file place the API token for the postcode info service. 

Install the composer dependencies

	php composer.phar install
	
Then, from the root of the repository

	bin/phpspec run --format=pretty -vvv --stop-on-failure
	
	
