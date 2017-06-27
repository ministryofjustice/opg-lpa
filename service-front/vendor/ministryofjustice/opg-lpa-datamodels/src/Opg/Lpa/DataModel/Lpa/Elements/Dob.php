<?php

namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\Common\Dob as BaseDob;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a date of birth.
 *
 * Class Dob
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Dob extends BaseDob
{
    protected $date;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        parent::loadValidatorMetadataCommon($metadata, "must-be-less-than-or-equal-to-today");
    }
}
