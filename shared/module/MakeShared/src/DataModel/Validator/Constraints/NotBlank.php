<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class NotBlank extends SymfonyConstraints\NotBlank
{
    use ValidatorPathTrait;

    public string $message = 'cannot-be-blank';
}
