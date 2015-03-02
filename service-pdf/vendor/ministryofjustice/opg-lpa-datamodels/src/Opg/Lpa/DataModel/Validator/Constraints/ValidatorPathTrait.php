<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

/**
 * This train should be included in any 'Validator\Constraints' class that wants to use
 * its original Symfony Validator (which should be all non-custom ones!).
 *
 * Class ValidatorPathTrait
 * @package Opg\Lpa\DataModel\Validator\Constraints
 */
trait ValidatorPathTrait {

    /**
     * Returns the name of the class that validates this constraint.
     *
     * This has been changed to point back to the original Symfony Validators.
     *
     * @return string
     *
     * @api
     */
    public function validatedBy()
    {
        $pathParts = explode('\\',get_class($this));
        return 'Symfony\\Component\\Validator\\Constraints\\'.end( $pathParts ).'Validator';
    }

} // trait