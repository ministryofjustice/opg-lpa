<?php

namespace Application\Form\Validator;

use Laminas\Validator\EmailAddress as LaminasEmailAddressValidator;

/**
 * Psalm rightly objects to overriding final but we cannot fix this right now
 * @psalm-suppress InvalidExtendClass, MethodSignatureMismatch
 */

class EmailAddress extends LaminasEmailAddressValidator
{
    /**
     * Overridden function to translate error messages
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $valid = parent::isValid($value);

        if ($valid === false && count($this->getMessages()) > 0) {
            $this->abstractOptions['messages'] = [
                'invalidEmailAddress' => 'Enter a valid email address'
            ];
        }

        return $valid;
    }
}
