<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @api
 */
class Collection extends Composite
{
    const MISSING_FIELD_ERROR = 1;
    const NO_SUCH_FIELD_ERROR = 2;

    protected static $errorNames = array(
        self::MISSING_FIELD_ERROR => 'MISSING_FIELD_ERROR',
        self::NO_SUCH_FIELD_ERROR => 'NO_SUCH_FIELD_ERROR',
    );

    public $fields = array();
    public $allowExtraFields = false;
    public $allowMissingFields = false;
    public $extraFieldsMessage = 'This field was not expected.';
    public $missingFieldsMessage = 'This field is missing.';

    public function getRequiredOptions()
    {
        return array('fields');
    }

    protected function getCompositeOption()
    {
        return 'fields';
    }
}
