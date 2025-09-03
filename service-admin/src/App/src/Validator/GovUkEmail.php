<?php

namespace App\Validator;

/**
 * Class GovUkEmail
 * @package App\Validator
 */
class GovUkEmail extends Regex
{
    /**
     * GovUkEmail constructor.
     *
     * @psalm-suppress MethodSignatureMismatch Will be resolved by LPA-3819
     */
    public function __construct()
    {
        $this->messageTemplates[self::NOT_MATCH] = 'invalid-email';

        parent::__construct('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@.*?(gov.uk)/');
    }
}
