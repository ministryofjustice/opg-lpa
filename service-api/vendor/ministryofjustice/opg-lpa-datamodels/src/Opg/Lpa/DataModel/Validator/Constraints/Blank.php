<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Blank extends SymfonyConstraints\Blank
{
    use ValidatorPathTrait;

    public $message = 'This value should be blank.';
}
