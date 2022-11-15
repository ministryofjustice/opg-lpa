<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class NotNull extends SymfonyConstraints\NotNull
{
    use ValidatorPathTrait;

    public string $message = 'cannot-be-null';
}
