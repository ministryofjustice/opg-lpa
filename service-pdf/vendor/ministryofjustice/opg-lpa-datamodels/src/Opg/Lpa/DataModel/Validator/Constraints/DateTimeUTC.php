<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DateTimeUTC extends Constraint {

    public $notUtcMessage = 'timezone-not-utc';
    public $notDateTimeMessage = 'not-datetime';

} // class
