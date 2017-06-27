<?php

namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\Common\Dob as BaseDob;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use RuntimeException;

/**
 * Represents a date of birth.
 *
 * Class Dob
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Dob extends BaseDob
{
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        self::loadValidatorMetadataCommon($metadata, null);
    }

    /**
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $v)
    {
        $mapped = parent::map($property, $v);
        if ($mapped === null || $mapped === '0') {
            throw new RuntimeException("Invalid date: $v. Date must exist and be in ISO-8601 format.");
        }

        return $mapped;
    }
}
