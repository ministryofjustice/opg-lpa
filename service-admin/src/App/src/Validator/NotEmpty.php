<?php

declare(strict_types=1);

namespace App\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\NotEmpty as LaminasNotEmpty;

final class NotEmpty extends AbstractValidator
{
    public const BOOLEAN       = LaminasNotEmpty::BOOLEAN;
    public const INTEGER       = LaminasNotEmpty::INTEGER;
    public const FLOAT         = LaminasNotEmpty::FLOAT;
    public const STRING        = LaminasNotEmpty::STRING;
    public const ZERO          = LaminasNotEmpty::ZERO;
    public const EMPTY_ARRAY   = LaminasNotEmpty::EMPTY_ARRAY;
    public const NULL          = LaminasNotEmpty::NULL;
    public const PHP           = LaminasNotEmpty::PHP;
    public const SPACE         = LaminasNotEmpty::SPACE;
    public const OBJECT        = LaminasNotEmpty::OBJECT;
    public const OBJECT_STRING = LaminasNotEmpty::OBJECT_STRING;
    public const OBJECT_COUNT  = LaminasNotEmpty::OBJECT_COUNT;
    public const ALL           = LaminasNotEmpty::ALL;

    public const INVALID = LaminasNotEmpty::INVALID;
    public const IS_EMPTY = LaminasNotEmpty::IS_EMPTY;

    /** @var array<string,string> */
    protected $messageTemplates = [
        self::IS_EMPTY => 'required',
        self::INVALID  => 'invalid-type',
    ];

    private LaminasNotEmpty $inner;

    public function __construct(array $options = [])
    {
        $defaultType =
            self::BOOLEAN |
            self::STRING |
            self::EMPTY_ARRAY |
            self::NULL |
            self::SPACE |
            self::OBJECT |
            self::OBJECT_STRING |
            self::OBJECT_COUNT;

        $type = $options['type'] ?? $defaultType;

        $this->inner = new LaminasNotEmpty(['type' => $type]);

        parent::__construct($options);
    }

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (!$this->inner->isValid($value)) {
            $messages = $this->inner->getMessages();

            if (array_key_exists(self::INVALID, $messages)) {
                $this->error(self::INVALID);
            } else {
                $this->error(self::IS_EMPTY);
            }

            return false;
        }

        return true;
    }
}
