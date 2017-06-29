<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Length extends SymfonyConstraints\Length
{
    use ValidatorPathTrait;

    public $minMessage = 'must-be-greater-than-or-equal:{{ limit }}';
    public $maxMessage = 'must-be-less-than-or-equal:{{ limit }}';
    public $exactMessage = 'length-must-equal:{{ limit }}';
}
