<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * Metadata for the LuhnValidator.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Tim Nagel <t.nagel@infinite.net.au>
 * @author Greg Knapp http://gregk.me/2011/php-implementation-of-bank-card-luhn-algorithm/
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Luhn extends SymfonyConstraints\Luhn
{
    use ValidatorPathTrait;

    const INVALID_CHARACTERS_ERROR = 1;
    const CHECKSUM_FAILED_ERROR = 2;

    protected static $errorNames = array(
        self::INVALID_CHARACTERS_ERROR => 'INVALID_CHARACTERS_ERROR',
        self::CHECKSUM_FAILED_ERROR => 'CHECKSUM_FAILED_ERROR',
    );

    public $message = 'Invalid card number.';
}
