<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class NotEqualTo extends AbstractComparison
{
    public $message = 'This value should not be equal to {{ compared_value }}.';
}
