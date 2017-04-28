<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class GreaterThanOrEqual extends AbstractComparison
{
    public $message = 'This value should be greater than or equal to {{ compared_value }}.';
}
