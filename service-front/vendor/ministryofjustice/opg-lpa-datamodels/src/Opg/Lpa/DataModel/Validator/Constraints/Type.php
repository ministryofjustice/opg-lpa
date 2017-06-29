<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Type extends SymfonyConstraints\Type
{
    use ValidatorPathTrait;

    public $message = 'expected-type:{{ type }}';
}
