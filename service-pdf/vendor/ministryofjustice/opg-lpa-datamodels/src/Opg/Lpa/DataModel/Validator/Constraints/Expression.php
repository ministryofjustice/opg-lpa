<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Expression extends SymfonyConstraints\Expression
{
    use ValidatorPathTrait;

    public $message = 'This value is not valid.';
    public $expression;

    public function getDefaultOption()
    {
        return 'expression';
    }

    public function getRequiredOptions()
    {
        return ['expression'];
    }

    public function getTargets()
    {
        return [
            self::CLASS_CONSTRAINT,
            self::PROPERTY_CONSTRAINT
        ];
    }

    public function validatedBy()
    {
        return 'validator.expression';
    }
}
