<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class IdenticalTo extends AbstractComparison
{
    public $message = 'This value should be identical to {{ compared_value_type }} {{ compared_value }}.';
}
