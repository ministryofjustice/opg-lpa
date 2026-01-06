<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class IsNull extends SymfonyConstraints\IsNull
{
    use ValidatorPathTrait;

    public string $message = 'must-be-null';
}
