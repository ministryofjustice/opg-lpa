# Lasting Power of Attorney API Client

This client if for accessing the Lasting Power of Attorney API Service.

Only v2 of the v2 Authentication Service is supported.
Add to project using Composer
-----------------------------

```json
{
	"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ministryofjustice/opg-lpa-api-client"
        }
    ],
    "require": {
    	"ministryofjustice/opg-lpa-api-client": "^4.0.0"
    }
}
```

Development
-----------

### Clone the repo and compose

```
git clone git@github.com:ministryofjustice/opg-lpa-api-client
cd opg-lpa-api-client
php composer.phar install
```

### Run the tests

`bin/phpspec run --format=pretty -vvv --stop-on-failure`


License
-------

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).

