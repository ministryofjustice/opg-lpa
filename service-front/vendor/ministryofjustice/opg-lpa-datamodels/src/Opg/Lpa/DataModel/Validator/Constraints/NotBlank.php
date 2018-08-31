<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class NotBlank extends SymfonyConstraints\NotBlank
{
    use ValidatorPathTrait;

    public $message = 'cannot-be-blank';
}
