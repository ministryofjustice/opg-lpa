<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Locale extends SymfonyConstraints\Locale
{
    use ValidatorPathTrait;

    public $message = 'This value is not a valid locale.';
}
