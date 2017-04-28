<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Language extends SymfonyConstraints\Language
{
    use ValidatorPathTrait;

    public $message = 'This value is not a valid language.';
}
