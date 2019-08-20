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
     */
    public function __construct()
    {
        $this->messageTemplates[self::NOT_MATCH] = 'invalid-email';

        parent::__construct('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@.*?(gov.uk)/');
    }
}
