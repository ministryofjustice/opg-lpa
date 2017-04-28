<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Type extends SymfonyConstraints\Type
{
    use ValidatorPathTrait;

    public $message = 'expected-type:{{ type }}';
    public $type;

    public function getDefaultOption()
    {
        return 'type';
    }

    public function getRequiredOptions()
    {
        return ['type'];
    }
}
