<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Type extends SymfonyConstraints\Type
{
    use ValidatorPathTrait;

    public string $message = 'expected-type:{{ type }}';
}
