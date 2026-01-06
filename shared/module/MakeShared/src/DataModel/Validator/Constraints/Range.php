<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Range extends SymfonyConstraints\Range
{
    use ValidatorPathTrait;

    public string $minMessage = 'must-be-greater-than-or-equal:{{ limit }}';
    public string $maxMessage = 'must-be-less-than-or-equal:{{ limit }}';
    public string $invalidMessage = 'expected-type:number';
}
