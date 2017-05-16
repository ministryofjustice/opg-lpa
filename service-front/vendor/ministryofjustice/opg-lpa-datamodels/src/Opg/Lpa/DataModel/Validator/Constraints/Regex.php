<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Regex extends SymfonyConstraints\Regex
{
    use ValidatorPathTrait;

    public $message = 'invalid-regex-match';
}
