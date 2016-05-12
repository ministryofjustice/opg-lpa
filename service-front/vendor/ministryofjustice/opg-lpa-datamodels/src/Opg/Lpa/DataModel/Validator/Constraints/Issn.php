<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Antonio J. García Lagar <aj@garcialagar.es>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Issn extends SymfonyConstraints\Issn
{
    use ValidatorPathTrait;

    const TOO_SHORT_ERROR = 1;
    const TOO_LONG_ERROR = 2;
    const MISSING_HYPHEN_ERROR = 3;
    const INVALID_CHARACTERS_ERROR = 4;
    const INVALID_CASE_ERROR = 5;
    const CHECKSUM_FAILED_ERROR = 6;

    protected static $errorNames = array(
        self::TOO_SHORT_ERROR => 'TOO_SHORT_ERROR',
        self::TOO_LONG_ERROR => 'TOO_LONG_ERROR',
        self::MISSING_HYPHEN_ERROR => 'MISSING_HYPHEN_ERROR',
        self::INVALID_CHARACTERS_ERROR => 'INVALID_CHARACTERS_ERROR',
        self::INVALID_CASE_ERROR => 'INVALID_CASE_ERROR',
        self::CHECKSUM_FAILED_ERROR => 'CHECKSUM_FAILED_ERROR',
    );

    public $message = 'This value is not a valid ISSN.';
    public $caseSensitive = false;
    public $requireHyphen = false;
}
