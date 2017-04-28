<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class False extends SymfonyConstraints\False
{
    use ValidatorPathTrait;

    public $message = 'This value should be false.';
}
