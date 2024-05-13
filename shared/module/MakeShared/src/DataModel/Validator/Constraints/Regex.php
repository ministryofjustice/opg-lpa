<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Regex extends SymfonyConstraints\Regex
{
    use ValidatorPathTrait;

    public string $message = 'invalid-regex-match';
}
