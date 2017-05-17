<?php

namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use DateTime;

/**
 * Represents a date of birth.
 *
 * Class Dob
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Dob extends AbstractData
{
    /**
     * A date of birth. The time component of the DateTime object should be ignored.
     *
     * @var DateTime
     */
    protected $date;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // As there is only 1 property, include NotBlank as there is no point this object existing without it.
        $lessThanOrEqualToToday = new Assert\LessThanOrEqual([
            'value' => new \DateTime('today')
        ]);

        $lessThanOrEqualToToday->message = "must-be-less-than-or-equal-to-today";

        $metadata->addPropertyConstraints('date', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
            $lessThanOrEqualToToday,
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
                    //  Assume the value is invalid until we have proved otherwise below
                    $isValid = false;

                    //  Split the array into components
                    $dateArr = explode('-', $v);

                    if (count($dateArr) == 3) {
                        //  Truncate the day value to lose any time data and try to create a DateTime object
                        $dateArr[2] = substr($dateArr[2], 0, 2);

                        //  Format the string and date to the same format to ensure that it is valid
                        $dateFormat = 'Y-m-d';
                        $date = DateTime::createFromFormat('Y-m-d', implode('-', $dateArr));

                        $isValid = ($date instanceof DateTime && strpos($v, $date->format($dateFormat)) === 0);
                    }

                    if (!$isValid) {
                        //  The date is invalid so return '0' instead of null
                        //  This will allow the NotBlank validation to pass so we can display an appropriate date not valid message
                        return '0';
                    }
                }

                return new DateTime($v);
        }

        return parent::map($property, $v);
    }
}
