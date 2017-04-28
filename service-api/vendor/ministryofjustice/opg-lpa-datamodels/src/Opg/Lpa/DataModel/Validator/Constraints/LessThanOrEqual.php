<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class LessThanOrEqual extends AbstractComparison
{
    public $message = 'must-be-less-than-or-equal:{{ compared_value }}';
}
