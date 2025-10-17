<?php

namespace Application\Form\Validator;

use Laminas\Validator\AbstractValidator;

class Correspondence extends AbstractValidator
{
    const AT_LEAST_ONE_SELECTED = 'at-least-one-option-needs-to-be-selected';

    protected $messageTemplates = [
        self::AT_LEAST_ONE_SELECTED => 'at-least-one-option-needs-to-be-selected',
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        if (($value['contactByPost'] | $value['contactByPhone'] | $value['contactByEmail']) == false) {
            $this->error(self::AT_LEAST_ONE_SELECTED);
            return false;
        }

        return true;
    }
}
