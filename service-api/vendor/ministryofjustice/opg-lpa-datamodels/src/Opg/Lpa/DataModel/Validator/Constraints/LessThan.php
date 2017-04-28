<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;
class LessThan extends AbstractComparison
{
    public $message = 'This value should be less than {{ compared_value }}.';
}
