<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Callback extends SymfonyConstraints\Callback
{
    use ValidatorPathTrait;

    public function getDefaultOption()
    {
        return 'callback';
    }

    public function getTargets()
    {
        return [
            self::CLASS_CONSTRAINT,
            self::PROPERTY_CONSTRAINT
        ];
    }
}
