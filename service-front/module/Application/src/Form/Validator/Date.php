<?php

namespace Application\Form\Validator;

use Zend\Validator\Date as DateValidator;
use DateTime;
use Exception;

class Date extends DateValidator
{
    const EMPTY_DATE = "emptyDate";

    public function __construct($options = [])
    {
        $this->messageTemplates = array_merge($this->messageTemplates, [
            self::EMPTY_DATE   => "Enter all the date fields",
        ]);

        parent::__construct($options);
    }

    public function isValid($value)
    {
        if (is_array($value)) {
            if (!array_key_exists('year', $value) || !array_key_exists('month', $value) || !array_key_exists('day', $value)) {
                throw new Exception('Invalid date array passed to Application\Form\Lpa\Validator\Date validator');
            }

            $day = $value['day'];
            $month = $value['month'];
            $year = $value['year'];

            if (empty($day) || empty($month) || empty($year)) {
                $this->error(self::EMPTY_DATE);
                return false;
            }

            if (!is_numeric($day) || !is_numeric($month) || !is_numeric($year)) {
                $this->error(parent::INVALID_DATE);
                return false;
            }

            //  Cast the values to integers to ensure the comparisons below are correct
            $day = (int) $day;
            $month = (int) $month;
            $year = (int) $year;

            //  If possible check the date value by using the same conversion that will take place in the Dob date model
            //  This is required to make sure that PHP doesn't 'shift' the time in an attempt to be helpful
            //  e.g. 1980-06-015 becomes 1st June 1980 at 5am
            $dateStr = implode('-', array_reverse($value));
            $date = date_parse_from_format(DateTime::ISO8601, $dateStr);

            if ($date['day'] != $day || $date['month'] != $month || $date['year'] != $year) {
                $this->error(parent::INVALID_DATE);
                return false;
            }

            //  Year minimum value is to ensure that the value is 4 digits long
            if (!checkdate($month, $day, $year)
                || !$this->intBetweenInclusive($day, 1, 31)
                || !$this->intBetweenInclusive($month, 1, 12)
                || !$this->intBetweenInclusive($year, 1000, 9999)) {

                $this->error(parent::INVALID_DATE);
                return false;
            }

            $value = implode('-', [$year, $month, $day]);
        }

        return parent::isValid($value);
    }

    private function intBetweenInclusive($value, $min, $max)
    {
        return (is_int($value) && $value >= $min && $value <= $max);
    }
}
