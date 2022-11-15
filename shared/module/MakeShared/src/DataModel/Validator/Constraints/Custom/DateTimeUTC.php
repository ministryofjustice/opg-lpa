<?php

namespace MakeShared\DataModel\Validator\Constraints\Custom;

use Symfony\Component\Validator\Constraint;

class DateTimeUTC extends Constraint
{
    public string $notUtcMessage = 'timezone-not-utc';
    public string $notDateTimeMessage = 'expected-type:DateTime';
}
