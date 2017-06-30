<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Null extends SymfonyConstraints\Null
{
    use ValidatorPathTrait;

    public $message = 'must-be-null';
}
