<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class LessThanOrEqual extends SymfonyConstraints\LessThanOrEqual
{
    use ValidatorPathTrait;

    public $message = 'must-be-less-than-or-equal:{{ compared_value }}';
}
