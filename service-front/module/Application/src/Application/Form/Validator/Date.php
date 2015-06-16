<?php
namespace Application\Form\Validator;

use Zend\Validator\Date as DateValidator;

class Date extends DateValidator
{
    const INVALID_YEAR_RANGE = "invalidYear";
    const INVALID_MONTH_RANGE = "invalidMonth";
    const INVALID_DAY_RANGE = "invalidDay";
    
    public function __construct($options = array())
    {
        $this->messageTemplates = array_merge($this->messageTemplates, [
                self::INVALID_YEAR_RANGE  => "year value must be between ".(date('Y') - 150)." and ".date('Y'),
                self::INVALID_MONTH_RANGE => "month value must be between 1 and 12",
                self::INVALID_DAY_RANGE   => "day value must be between 1 and 31",
        ]);
        
        parent::__construct($options);
    }
    
    public function isValid($value)
    {
        if(is_array($value)) {
            
            $isInvalid = false;
            
            if(!array_key_exists('year', $value)||!array_key_exists('month', $value)||!array_key_exists('day', $value)) {
                throw new \Exception('Invalid date array passed to Application\Form\Lpa\Validator\Date validator');
            }
            
            if(($value['day'] < 1)||($value['day'] > 31)) {
                $this->error(self::INVALID_DAY_RANGE);
                $isInvalid = true;
            }
            
            if(($value['month'] < 1)||($value['month'] > 12)) {
                $this->error(self::INVALID_MONTH_RANGE);
                $isInvalid = true;
            }
            
            if(($value['year'] < ((int)date('Y') - 150))||($value['year'] > (int)date('Y'))) {
                $this->error(self::INVALID_YEAR_RANGE);
                $isInvalid = true;
            }
            
            if(!$isInvalid && !checkdate((int)$value['month'],(int)$value['day'],(int)$value['year'])) {
                $this->error(parent::INVALID_DATE);
                $isInvalid = true;
            }
            
            if($isInvalid) {
                return false;
            }
            
            $value = implode('-', [$value['year'],$value['month'],$value['day']]);
        }
        
        return parent::isValid($value);
    }
    
}