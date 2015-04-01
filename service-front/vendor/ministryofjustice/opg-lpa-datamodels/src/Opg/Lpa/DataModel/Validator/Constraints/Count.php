<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @api
 */
class Count extends SymfonyConstraints\Count
{

    const TOO_FEW_ERROR = 1;
    const TOO_MANY_ERROR = 2;

    protected static $errorNames = array(
        self::TOO_FEW_ERROR => 'TOO_FEW_ERROR',
        self::TOO_MANY_ERROR => 'TOO_MANY_ERROR',
    );

    public $minMessage = 'must-be-greater-than-or-equal:{{ limit }}';
    public $maxMessage = 'must-be-less-than-or-equal:{{ limit }}';
    public $exactMessage = 'length-must-equal:{{ limit }}';

}
