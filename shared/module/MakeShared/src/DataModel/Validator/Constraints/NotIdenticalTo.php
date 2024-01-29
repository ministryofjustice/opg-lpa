<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class NotIdenticalTo extends SymfonyConstraints\NotIdenticalTo
{
    use ValidatorPathTrait;

    public string $message = 'cannot-be-identical-to:{{ compared_value }}';
}
