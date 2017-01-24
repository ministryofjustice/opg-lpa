<?php
namespace Application\Form\Validator;

use Zend\Validator\Date as DateValidator;

class Date extends DateValidator
{
    const EMPTY_DATE = "emptyDate";

    public function __construct($options = array())
    {
        $this->messageTemplates = array_merge($this->messageTemplates, [
            self::EMPTY_DATE   => "Please enter all the date fields",
        ]);

        parent::__construct($options);
    }

    public function isValid($value)
    {
        if (is_array($value)) {
            if (!array_key_exists('year', $value) || !array_key_exists('month', $value) || !array_key_exists('day', $value)) {
                throw new \Exception('Invalid date array passed to Application\Form\Lpa\Validator\Date validator');
            }

            $day = (int) $value['day'];
            $month = (int) $value['month'];
            $year = (int) $value['year'];

            if (empty($day) || empty($month) || empty($year)) {
                $this->error(self::EMPTY_DATE);
                return false;
            }

            if (!checkdate($month, $day, $year)
                || !$this->intBetweenInclusive($day, 1, 31)
                || !$this->intBetweenInclusive($month, 1, 12)
                || !$this->intBetweenInclusive($year, 1, 9999)) {

                $this->error(parent::INVALID_DATE);
                return false;
            }

            $value = implode('-', [$year, $month, $day]);
        }

        return parent::isValid($value);
    }

    private function intBetweenInclusive($value, $min, $max)
    {
        if (is_int($value)) {
            return ($value >= $min && $value <= $max);
        }

        return false;
    }
}
