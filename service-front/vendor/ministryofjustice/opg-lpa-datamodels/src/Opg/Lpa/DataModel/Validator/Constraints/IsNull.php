<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class IsNull extends SymfonyConstraints\IsNull
{
    use ValidatorPathTrait;

    public $message = 'must-be-null';
}
