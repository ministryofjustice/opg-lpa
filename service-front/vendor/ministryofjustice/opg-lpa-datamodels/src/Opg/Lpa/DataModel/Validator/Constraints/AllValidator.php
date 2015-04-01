<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AllValidator as SymfonyAllValidator;

/**
 * Changed AllValidator so that it's not applied if value is not an array.
 *
 * Class AllValidator
 * @package Opg\Lpa\DataModel\Validator\Constraints
 */
class AllValidator extends SymfonyAllValidator {

    public function validate($value, Constraint $constraint){

        if( !is_array($value) ){
            return;
        }

        parent::validate( $value, $constraint );

    } // function

} // class
