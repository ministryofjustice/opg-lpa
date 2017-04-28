<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Traverse extends SymfonyConstraints\Traverse
{
    use ValidatorPathTrait;

    public $traverse = true;

    public function getDefaultOption()
    {
        return 'traverse';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
