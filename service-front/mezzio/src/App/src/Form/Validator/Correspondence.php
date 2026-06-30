<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Laminas\Validator\AbstractValidator;

class Correspondence extends AbstractValidator
{
    public const AT_LEAST_ONE_SELECTED = 'at-least-one-option-needs-to-be-selected';

    protected $messageTemplates = [
        self::AT_LEAST_ONE_SELECTED => 'at-least-one-option-needs-to-be-selected',
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        // The fieldset may return a hydrated model object (ClassMethodsHydrator) or a
        // plain array depending on the processing stage — handle both forms.
        if (is_object($value)) {
            $byPost  = $value->contactByPost  ?? false;
            $byPhone = $value->contactByPhone ?? false;
            $byEmail = $value->contactByEmail ?? false;
        } elseif (is_array($value)) {
            $byPost  = $value['contactByPost']  ?? false;
            $byPhone = $value['contactByPhone'] ?? false;
            $byEmail = $value['contactByEmail'] ?? false;
        } else {
            $this->error(self::AT_LEAST_ONE_SELECTED);
            return false;
        }

        if (($byPost | $byPhone | $byEmail) == false) {
            $this->error(self::AT_LEAST_ONE_SELECTED);
            return false;
        }

        return true;
    }
}
