<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class LessThanOrEqual extends SymfonyConstraints\LessThanOrEqual
{
    use ValidatorPathTrait;

    public string $message = 'must-be-less-than-or-equal:{{ compared_value }}';
}
