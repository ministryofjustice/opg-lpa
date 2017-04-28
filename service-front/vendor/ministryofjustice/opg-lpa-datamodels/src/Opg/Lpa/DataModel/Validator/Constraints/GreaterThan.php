<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class GreaterThan extends AbstractComparison
{
    public $message = 'This value should be greater than {{ compared_value }}.';
}
