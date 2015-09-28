OPG LPA API Client
==================

This version of the API client works with the LPA v2 Auth Server and no longer works with the LPA v1 Auth Server.

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

        bin/phpspec run --format=pretty -vvv --stop-on-failure



