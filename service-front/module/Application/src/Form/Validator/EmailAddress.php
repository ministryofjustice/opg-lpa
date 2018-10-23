<?php

namespace Application\Form\Validator;

use Zend\Validator\EmailAddress as ZfEmailAddressValidator;

class EmailAddress extends ZfEmailAddressValidator
{
    /**
     * Overridden function to translate error messages
     *
     * @param string $value
     * @return bool
     */
    public function isValid($value)
    {
        $valid = parent::isValid($value);

        if ($valid === false && count($this->getMessages()) > 0) {
            $this->abstractOptions['messages'] = [
                'Enter a valid email address'
            ];
        }

        return $valid;
    }
}
