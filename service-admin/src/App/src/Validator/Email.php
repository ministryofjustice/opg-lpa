<?php

namespace App\Validator;

use Laminas\Validator\EmailAddress;

/**
 * Class Email
 * @package App\Validator
 *
 * @psalm-suppress InvalidExtendClass Will be resolved in LPA-3821
 */
class Email extends EmailAddress
{
    /**
     * Email constructor.
     * @param array<string, mixed> $options
     *
     * @psalm-suppress MethodSignatureMismatch Will be resolved in LPA-3821
     * @psalm-suppress MoreSpecificImplementedParamType Will be resolved in LPA-3821
     */
    public function __construct($options = [])
    {
        //  Merge the custom error references into the message templates
        // - do this to simplify the error reference for translation
        $this->messageTemplates = array_merge($this->messageTemplates, [
            self::INVALID            => 'invalid-email',
            self::INVALID_FORMAT     => 'invalid-email',
            self::INVALID_HOSTNAME   => 'invalid-email',
            self::INVALID_MX_RECORD  => 'invalid-email',
            self::INVALID_SEGMENT    => 'invalid-email',
            self::DOT_ATOM           => 'invalid-email',
            self::QUOTED_STRING      => 'invalid-email',
            self::INVALID_LOCAL_PART => 'invalid-email',
            self::LENGTH_EXCEEDED    => 'invalid-email',
        ]);

        parent::__construct($options);
    }
}
