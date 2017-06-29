<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class NotNull extends SymfonyConstraints\NotNull
{
    use ValidatorPathTrait;

    public $message = 'cannot-be-null';
}
