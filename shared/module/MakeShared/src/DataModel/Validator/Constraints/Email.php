<?php

namespace MakeShared\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Email extends SymfonyConstraints\Email
{
    use ValidatorPathTrait;

    public string $message = 'invalid-email-address';
}
