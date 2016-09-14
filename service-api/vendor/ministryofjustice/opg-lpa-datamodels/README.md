# Lasting Power of Attorney Data Models

The lasting power of attorney (LPA) data models are a set of PHP classes that we use to represent and validate a LPA document within our various systems.


## Installation with Composer

Add the following into your composer.json, then call `php composer.phar install`. 

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ministryofjustice/opg-lpa-datamodels"
        }
    ],
    "require": {
        "ministryofjustice/opg-lpa-datamodels": "^1.0.0",
    }
}
```

## Validation

The Data Models include validation method. [Validator errors responses are documented here](docs/validation.md).
 

## Tests


#### Suite

        bin/phpspec run --format=pretty -vvv --stop-on-failure

#### Single spec file

        bin/phpspec run spec/Opg/Lpa/DataModel/Lpa/Elements/AddressSpec.php
        
License
-------

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
        