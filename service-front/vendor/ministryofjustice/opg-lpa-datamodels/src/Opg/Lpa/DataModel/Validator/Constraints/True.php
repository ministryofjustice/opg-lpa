<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class True extends SymfonyConstraints\True
{
    use ValidatorPathTrait;

    public $message = 'This value should be true.';
}
