<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Iban extends SymfonyConstraints\Iban
{
    use ValidatorPathTrait;

    const TOO_SHORT_ERROR = 1;
    const INVALID_COUNTRY_CODE_ERROR = 2;
    const INVALID_CHARACTERS_ERROR = 3;
    const INVALID_CASE_ERROR = 4;
    const CHECKSUM_FAILED_ERROR = 5;

    protected static $errorNames = [
        self::TOO_SHORT_ERROR => 'TOO_SHORT_ERROR',
        self::INVALID_COUNTRY_CODE_ERROR => 'INVALID_COUNTRY_CODE_ERROR',
        self::INVALID_CHARACTERS_ERROR => 'INVALID_CHARACTERS_ERROR',
        self::INVALID_CASE_ERROR => 'INVALID_CASE_ERROR',
        self::CHECKSUM_FAILED_ERROR => 'CHECKSUM_FAILED_ERROR',
    ];

    public $message = 'This is not a valid International Bank Account Number (IBAN).';
}
