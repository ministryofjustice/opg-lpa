<?php
namespace Opg\Lpa\DataModel\Validator\Constraints\Custom;

use Symfony\Component\Validator\Constraint;

class UniqueIdInArray extends Constraint {

    public $notUnique = 'id-not-unique';

} // class
