<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

abstract class Existence extends Composite
{
    public $constraints = [];

    public function getDefaultOption()
    {
        return 'constraints';
    }

    protected function getCompositeOption()
    {
        return 'constraints';
    }
}
