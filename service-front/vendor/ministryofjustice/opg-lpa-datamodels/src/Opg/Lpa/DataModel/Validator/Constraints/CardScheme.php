<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * Metadata for the CardSchemeValidator.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Tim Nagel <t.nagel@infinite.net.au>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CardScheme extends SymfonyConstraints\CardScheme
{
    use ValidatorPathTrait;

    const NOT_NUMERIC_ERROR = 1;
    const INVALID_FORMAT_ERROR = 2;

    protected static $errorNames = array(
        self::NOT_NUMERIC_ERROR => 'NOT_NUMERIC_ERROR',
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
    );

    public $message = 'Unsupported card type or invalid card number.';
    public $schemes;

    public function getDefaultOption()
    {
        return 'schemes';
    }

    public function getRequiredOptions()
    {
        return array('schemes');
    }
}
