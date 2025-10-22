<?php

declare(strict_types=1);

namespace App\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\EmailAddress;

final class Email extends AbstractValidator
{
    public const INVALID             = 'invalid';
    public const INVALID_TYPE        = 'invalid-type';

    /** @var array<string,string> */
    protected $messageTemplates = [
        EmailAddress::INVALID            => 'invalid-email',
        EmailAddress::INVALID_FORMAT     => 'invalid-email',
        EmailAddress::INVALID_HOSTNAME   => 'invalid-email',
        EmailAddress::INVALID_MX_RECORD  => 'invalid-email',
        EmailAddress::INVALID_SEGMENT    => 'invalid-email',
        EmailAddress::DOT_ATOM           => 'invalid-email',
        EmailAddress::QUOTED_STRING      => 'invalid-email',
        EmailAddress::INVALID_LOCAL_PART => 'invalid-email',
        EmailAddress::LENGTH_EXCEEDED    => 'invalid-email',
        self::INVALID                    => 'invalid-email',
        self::INVALID_TYPE               => 'invalid-email',
    ];
    private EmailAddress $emailValidator;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->emailValidator = new EmailAddress($options);
    }

    public function isValid($value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID_TYPE);
            return false;
        }
        if ($this->emailValidator->isValid($value)) {
            return true;
        }

        $innerMessages = array_keys($this->emailValidator->getMessages());
        if ($innerMessages === []) {
            $this->error(self::INVALID);
            return false;
        }
        foreach ($innerMessages as $key) {
            $this->error(\array_key_exists($key, $this->messageTemplates) ? $key : self::INVALID);
        }
        return false;
    }
}
