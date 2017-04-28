<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Country extends SymfonyConstraints\Country
{
    use ValidatorPathTrait;

    public $message = 'invalid-country-code';
}
