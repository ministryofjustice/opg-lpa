<?php

namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use DateTime;
use RuntimeException;

/**
 * Represents a date of birth.
 *
 * Class Dob
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Dob extends AbstractData
{
    /**
     * @var \DateTime A date of birth. The time component of the DateTime object should be ignored.
     */
    protected $date;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // As there is only 1 property, include NotBlank as there is no point this object existing without it.
        $metadata->addPropertyConstraints('date', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
            new Assert\LessThanOrEqual([
                'value' => new \DateTime('today')
            ]),
        ]);
    }

    /**
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $v)
    {
        switch ($property) {
            case 'date':
                if ($v instanceof DateTime || is_null($v)) {
                    return $v;
                }

                if (is_string($v)) {
                    $date = date_parse_from_format(DateTime::ISO8601, $v);

                    if (!checkdate(@$date['month'], @$date['day'], @$date['year'])) {
                        throw new RuntimeException("Invalid date: $v. Date must exist and be in ISO-8601 format.");
                    }
                }

                return new DateTime($v);
        }

        return parent::map($property, $v);
    }
}
