<?php

namespace Opg\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use DateTime;

/**
 * Represents a date of birth.
 *
 * Class Dob
 * @package Opg\Lpa\DataModel\Common
 */
class Dob extends AbstractData
{
    /**
     * A date of birth. The time component of the DateTime object should be ignored.
     *
     * @var DateTime
     */
    protected $date;

    protected static function loadValidatorMetadataCommon(ClassMetadata $metadata, $message)
    {
        // As there is only 1 property, include NotBlank as there is no point this object existing without it.
        $lessThanOrEqualToToday = new Assert\LessThanOrEqual([
            'value' => new \DateTime('today')
        ]);

        if ($message !== null) {
            $lessThanOrEqualToToday->message = $message;
        }

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
                    //  Split the array into components
                    $dateArr = explode('-', $v);

                    if (count($dateArr) == 3) {
                        //  Truncate the day value to lose any time data and try to create a DateTime object
                        $dateArr[2] = substr($dateArr[2], 0, 2);

                        //  If required add any leading zeros to the day and month
                        $dateArr[1] = str_pad($dateArr[1], 2, '0', STR_PAD_LEFT);
                        $dateArr[2] = str_pad($dateArr[2], 2, '0', STR_PAD_LEFT);

                        //  Remove any leading zeros from the year
                        $dateArr[0] = ltrim($dateArr[0], '0');

                        //  Format the string and date to the same format to ensure that it is valid
                        $dateFormat = 'Y-m-d';
                        $dateIn = implode('-', $dateArr);
                        $date = DateTime::createFromFormat('Y-m-d', $dateIn);

                        if ($date instanceof DateTime && strpos($dateIn, $date->format($dateFormat)) === 0) {
                            return $date;
                        }
                    }
                }

                //  The date is invalid so return '0' instead of null
                //  This will allow the NotBlank validation to pass
                //  so we can display an appropriate date not valid message
                return '0';
        }

        return parent::map($property, $v);
    }
}
