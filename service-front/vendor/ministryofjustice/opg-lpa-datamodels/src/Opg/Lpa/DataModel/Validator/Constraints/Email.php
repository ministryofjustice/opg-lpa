<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Email extends SymfonyConstraints\Email
{
    use ValidatorPathTrait;

    public $message = 'invalid-email-address';
}
