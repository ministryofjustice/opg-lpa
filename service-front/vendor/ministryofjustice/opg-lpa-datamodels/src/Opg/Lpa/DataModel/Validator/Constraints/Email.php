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
class Email extends SymfonyConstraints\Email
{
    use ValidatorPathTrait;

    const INVALID_FORMAT_ERROR = 1;
    const MX_CHECK_FAILED_ERROR = 2;
    const HOST_CHECK_FAILED_ERROR = 3;

    protected static $errorNames = array(
        self::INVALID_FORMAT_ERROR => 'STRICT_CHECK_FAILED_ERROR',
        self::MX_CHECK_FAILED_ERROR => 'MX_CHECK_FAILED_ERROR',
        self::HOST_CHECK_FAILED_ERROR => 'HOST_CHECK_FAILED_ERROR',
    );

    public $message = 'invalid-email-address';

}
