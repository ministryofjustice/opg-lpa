<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Length extends SymfonyConstraints\Length
{
    use ValidatorPathTrait;

    const TOO_SHORT_ERROR = 1;
    const TOO_LONG_ERROR = 2;

    protected static $errorNames = [
        self::TOO_SHORT_ERROR => 'TOO_SHORT_ERROR',
        self::TOO_LONG_ERROR => 'TOO_LONG_ERROR',
    ];

    public $minMessage = 'must-be-greater-than-or-equal:{{ limit }}';
    public $maxMessage = 'must-be-less-than-or-equal:{{ limit }}';
    public $exactMessage = 'length-must-equal:{{ limit }}';
}
