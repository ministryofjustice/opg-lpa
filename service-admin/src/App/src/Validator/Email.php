<?php

declare(strict_types=1);

namespace App\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\EmailAddress;

final class Email extends AbstractValidator
{
    public const INVALID             = 'invalid';
    public const INVALID_FORMAT      = 'invalid-format';
    public const INVALID_HOSTNAME    = 'invalid-hostname';
    public const INVALID_MX_RECORD   = 'invalid-mx-record';
    public const INVALID_SEGMENT     = 'invalid-segment';
    public const DOT_ATOM            = 'dot-atom';
    public const QUOTED_STRING       = 'quoted-string';
    public const INVALID_LOCAL_PART  = 'invalid-local-part';
    public const LENGTH_EXCEEDED     = 'length-exceeded';
    public const INVALID_TYPE        = 'invalid-type';

    /** @var array<string,string> */
    protected $messageTemplates = [
        self::INVALID            => 'invalid-email',
        self::INVALID_FORMAT     => 'invalid-email',
        self::INVALID_HOSTNAME   => 'invalid-email',
        self::INVALID_MX_RECORD  => 'invalid-email',
        self::INVALID_SEGMENT    => 'invalid-email',
        self::DOT_ATOM           => 'invalid-email',
        self::QUOTED_STRING      => 'invalid-email',
        self::INVALID_LOCAL_PART => 'invalid-email',
        self::LENGTH_EXCEEDED    => 'invalid-email',
        self::INVALID_TYPE       => 'invalid-email',
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
