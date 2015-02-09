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

### Run the tests

	bin/phpspec run --format=pretty



