<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\CountValidator as SymfonyCountValidator;

/**
 * Changed CountValidator so that it's not applied if value is not an array.
 *
 * Class CountValidator
 * @package Opg\Lpa\DataModel\Validator\Constraints
 */
class CountValidator extends SymfonyCountValidator {

    public function validate($value, Constraint $constraint){

        if( !is_array($value) ){
            return;
        }

        parent::validate( $value, $constraint );

    } // function

} // class
