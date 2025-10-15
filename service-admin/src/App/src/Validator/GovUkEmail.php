<?php

namespace App\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Regex;

final class GovUkEmail extends AbstractValidator
{
    public const INVALID_TYPE  = 'govUkEmailInvalidType';
    public const NOT_GOVUK     = 'govUkEmailNotGovUk';

    protected $messageTemplates = [
        self::INVALID_TYPE => 'Invalid type given. String expected',
        self::NOT_GOVUK    => 'Please use a GOV.UK email address',
    ];

    private Regex $regex;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $pattern = $options['pattern'] ?? '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@.*?(gov.uk)/';

        $this->regex = new Regex([
            'pattern'  => $pattern,
            'messages' => [
                Regex::NOT_MATCH => $this->messageTemplates[self::NOT_GOVUK],
            ],
        ]);
    }

    /**
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID_TYPE);
            return false;
        }

        if (!$this->regex->isValid($value)) {
            $this->error(self::NOT_GOVUK);
            return false;
        }

        return true;
    }
}
