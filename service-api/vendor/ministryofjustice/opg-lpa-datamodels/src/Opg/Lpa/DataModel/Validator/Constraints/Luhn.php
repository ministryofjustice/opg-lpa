<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Luhn extends SymfonyConstraints\Luhn
{
    use ValidatorPathTrait;

    const INVALID_CHARACTERS_ERROR = 1;
    const CHECKSUM_FAILED_ERROR = 2;

    protected static $errorNames = [
        self::INVALID_CHARACTERS_ERROR => 'INVALID_CHARACTERS_ERROR',
        self::CHECKSUM_FAILED_ERROR => 'CHECKSUM_FAILED_ERROR',
    ];

    public $message = 'Invalid card number.';
}
