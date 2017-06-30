<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Count extends SymfonyConstraints\Count
{
    use ValidatorPathTrait;

    public $minMessage = 'must-be-greater-than-or-equal:{{ limit }}';
    public $maxMessage = 'must-be-less-than-or-equal:{{ limit }}';
    public $exactMessage = 'length-must-equal:{{ limit }}';
}
