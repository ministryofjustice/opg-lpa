<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @api
 */
class DateTime extends SymfonyConstraints\DateTime
{
    use ValidatorPathTrait;

    const INVALID_FORMAT_ERROR = 1;
    const INVALID_DATE_ERROR = 2;
    const INVALID_TIME_ERROR = 3;

    protected static $errorNames = array(
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
        self::INVALID_DATE_ERROR => 'INVALID_DATE_ERROR',
        self::INVALID_TIME_ERROR => 'INVALID_TIME_ERROR',
    );

    public $message = 'expected-type:DateTime';
}
