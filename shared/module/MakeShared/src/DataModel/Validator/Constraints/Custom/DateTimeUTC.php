<?php

namespace MakeShared\DataModel\Validator\Constraints\Custom;

use Symfony\Component\Validator\Constraint;

class DateTimeUTC extends Constraint
{
    public $notUtcMessage = 'timezone-not-utc';
    public $notDateTimeMessage = 'expected-type:DateTime';
}
