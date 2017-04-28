<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Currency extends SymfonyConstraints\Currency
{
    use ValidatorPathTrait;

    public $message = 'This value is not a valid currency.';
}
