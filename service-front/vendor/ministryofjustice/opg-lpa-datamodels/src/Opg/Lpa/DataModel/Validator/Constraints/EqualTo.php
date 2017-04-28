<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class EqualTo extends AbstractComparison
{
    public $message = 'This value should be equal to {{ compared_value }}.';
}
