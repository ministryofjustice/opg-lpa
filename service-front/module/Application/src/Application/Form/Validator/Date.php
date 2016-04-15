<?php
namespace Application\Form\Validator;

use Zend\Validator\Date as DateValidator;

class Date extends DateValidator
{
    public function __construct($options = array())
    {
        parent::__construct($options);
    }
    
    public function isValid($value)
    {
        if(is_array($value)) {
            
            if(!array_key_exists('year', $value)||!array_key_exists('month', $value)||!array_key_exists('day', $value)) {
                throw new \Exception('Invalid date array passed to Application\Form\Lpa\Validator\Date validator');
            }
            
            if(!checkdate((int)$value['month'],(int)$value['day'],(int)$value['year'])) {
                $this->error(parent::INVALID_DATE);
                return false;
            }
            
            $value = implode('-', [$value['year'],$value['month'],$value['day']]);
        }
        
        return parent::isValid($value);
    }
    
}