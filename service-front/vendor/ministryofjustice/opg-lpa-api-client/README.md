OPG LPA API Client
==================

A client wrapper for the OPG LPA REST API (opg-lpa-api)

Add to project using Composer
-----------------------------

	{
		"repositories": [
	        {
	            "type": "vcs",
	            "url": "https://github.com/ministryofjustice/opg-lpa-api-client"
	        }
	    ],
	    "require": {
	    	"ministryofjustice/opg-lpa-api-client": "dev-develop"
	    }
	}

Configure
---------

Copy config/opg-lpa-api-client.php to the config directory at the root of your project and configure to taste.

Development
-----------

### Clone the repo and compose

    git clone git@github.com:ministryofjustice/opg-lpa-api-client
    cd opg-lpa-api-client
    php composer.phar install

## PHPSpec Unit Test

### Run the tests

	bin/phpspec run --format=pretty

### Create a new test

Example for AuthReponse class

	bin/phpspec desc Opg/Lpa/Api/Client/Response/AuthResponse
	
Write the specs by editing the file /spec/Opg/Lpa/Api/Client/Response/AuthResponseSpec.php


