Lasting Power of Attorney Data Models
==============
The lasting power of attorney (LPA) data models are a set of PHP classes that we use to represent and validate a LPA application within our various systems.


Installation with Composer
--------------------

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
        "ministryofjustice/opg-lpa-datamodels": "dev-develop",
    }
}
```
 

License
-------

The Lasting Power of Attorney Data Models are released under the MIT license, a copy of which can be found in ``LICENSE``.

Tests
-----

## Create

        bin/phpspec desc Opg/Lpa/DataModel/Lpa/Elements/PhoneNumber

## Run

### Suite

        bin/phpspec run --format=pretty -vvv --stop-on-failure

### Single spec file

        bin/phpspec run spec/Opg/Lpa/DataModel/Lpa/Elements/AddressSpec.php