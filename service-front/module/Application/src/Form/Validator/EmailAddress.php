<?php

namespace Application\Form\Validator;

use Laminas\Validator\EmailAddress as LaminasEmailAddressValidator;

class EmailAddress extends LaminasEmailAddressValidator
{
    /**
     * Overridden function to translate error messages
     *
     * @param string $value
     * @return bool
     */
    public function isValid($value): bool
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
