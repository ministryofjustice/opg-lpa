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
     * Parse supplied date components into a valid Date of Birth based on internal parsing rules
     *
     * @param $v
     * @return mixed
     */
    public static function parseDob($v)
    {
        if ($v instanceof DateTime || is_null($v)) {
            return $v;
        }

        if (is_string($v)) {
            //  Split the array into components
            $timeIndex = strpos($v, 'T');
            $dateArr = explode('-', $v);
            $timeArr = array('00', '00', '00.000000+0000');
            if ($timeIndex) {
                $dateArr = explode('-', substr($v, 0, $timeIndex));
                $timeArr = explode(':', substr($v, $timeIndex + 1));
            }

            if (count($dateArr) == 3) {
                //  Remove any leading zeros from the date components
                $dateArr[0] = ltrim($dateArr[0], '0');
                $dateArr[1] = ltrim($dateArr[1], '0');
                $dateArr[2] = ltrim($dateArr[2], '0');

                //  Truncate the day value to lose any time data and try to create a DateTime object
                $dateArr[2] = substr($dateArr[2], 0, 2);

                //  If required add any leading zeros to the day and month
                $dateArr[1] = str_pad($dateArr[1], 2, '0', STR_PAD_LEFT);
                $dateArr[2] = str_pad($dateArr[2], 2, '0', STR_PAD_LEFT);

                //  Format the string and date to the same format to ensure that it is valid
                $dateFormat = 'Y-m-d H:i:s.uO';
                $dateIn = implode('-', $dateArr) . ' ' . implode(':', $timeArr);
                $parsedDate = DateTime::createFromFormat($dateFormat, $dateIn);

                if ($parsedDate instanceof DateTime && strpos($dateIn, $parsedDate->format($dateFormat)) === 0) {
                    return $parsedDate;
                }
            }
        }

        //  The date is invalid so return '0' instead of null
        //  This will allow the NotBlank validation to pass
        //  so we can display an appropriate date not valid message
        return '0';
    }

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
                return self::parseDob($v);
        }

        return parent::map($property, $v);
    }
}
