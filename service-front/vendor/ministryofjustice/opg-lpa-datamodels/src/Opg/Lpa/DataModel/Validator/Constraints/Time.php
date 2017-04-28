<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Time extends SymfonyConstraints\Time
{
    use ValidatorPathTrait;

    const INVALID_FORMAT_ERROR = 1;
    const INVALID_TIME_ERROR = 2;

    protected static $errorNames = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
        self::INVALID_TIME_ERROR => 'INVALID_TIME_ERROR',
    ];

    public $message = 'This value is not a valid time.';
}
