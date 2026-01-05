<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Count extends SymfonyConstraints\Count
{
    use ValidatorPathTrait;

    public string $minMessage = 'must-be-greater-than-or-equal:{{ limit }}';
    public string $maxMessage = 'must-be-less-than-or-equal:{{ limit }}';
    public string $exactMessage = 'length-must-equal:{{ limit }}';
}
