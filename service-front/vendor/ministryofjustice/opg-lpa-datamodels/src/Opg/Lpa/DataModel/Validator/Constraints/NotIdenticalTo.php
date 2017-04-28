<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class NotIdenticalTo extends AbstractComparison
{
    public $message = 'This value should not be identical to {{ compared_value_type }} {{ compared_value }}.';
}
