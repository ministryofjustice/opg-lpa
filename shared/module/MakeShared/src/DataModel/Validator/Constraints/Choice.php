<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Choice extends SymfonyConstraints\Choice
{
    use ValidatorPathTrait;

    //  Values are overwritten in the constructor
    public string $message = 'invalid-value-selected';
    public string $multipleMessage = 'invalid-values-selected';
    public string $minMessage = 'minimum-number-of-values:{{ limit }}';
    public string $maxMessage = 'maximum-number-of-values:{{ limit }}';


    public function __construct(
        string|array|null $options = null,
        ?array $choices = null,
        callable|string|null $callback = null,
        ?bool $multiple = null,
        ?bool $strict = null,
        ?int $min = null,
        ?int $max = null,
        ?string $message = null,
        ?string $multipleMessage = null,
        ?string $minMessage = null,
        ?string $maxMessage = null,
        ?array $groups = null,
        mixed $payload = null,
        ?bool $match = null,
    ) {
        // Include the allowed values in the error message
        if (isset($choices)) {
            $this->message = 'allowed-values:' . implode(',', $choices);
            $this->multipleMessage = 'allowed-values:' . implode(',', $choices);
        }

        parent::__construct(
            $options,
            $choices,
            $callback,
            $multiple,
            $strict,
            $min,
            $max,
            $message,
            $multipleMessage,
            $minMessage,
            $maxMessage,
            $groups,
            $payload,
            $match
        );
    }
}
