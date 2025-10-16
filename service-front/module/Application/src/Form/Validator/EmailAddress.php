<?php

namespace Application\Form\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\EmailAddress as LaminasEmailAddressValidator;

class EmailAddress extends AbstractValidator
{
    private LaminasEmailAddressValidator $validator;

    public const string INVALID_EMAIL  = 'invalidEmailAddress';

    protected $messageTemplates = [
        self::INVALID_EMAIL => 'Enter a valid email address',
    ];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->validator = new LaminasEmailAddressValidator();
    }

    /**
     * Overridden function to translate error messages
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $valid = $this->validator->isValid($value);

        if ($valid === false && count($this->validator->getMessages()) > 0) {
            $this->abstractOptions['messages'] = $this->messageTemplates;
        }

        return $valid;
    }
}
